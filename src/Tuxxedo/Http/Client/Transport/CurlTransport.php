<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http\Client\Transport;

use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientTimeout;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Stream\Stream;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerManagerInterface;
use Tuxxedo\Temporal\DurationInterface;

class CurlTransport implements TransportInterface
{
    private readonly ?\CurlSharePersistentHandle $share;

    public function __construct(
        private readonly SerializerManagerInterface $serializerManager,
        bool $persistentShare = true,
    ) {
        $this->share = $persistentShare
            ? \curl_share_init_persistent(
                [
                    \CURL_LOCK_DATA_DNS,
                    \CURL_LOCK_DATA_SSL_SESSION,
                    \CURL_LOCK_DATA_CONNECT,
                ],
            )
            : null;
    }

    public function send(
        iterable $requests,
        TransportOptionsInterface $options,
        int $concurrency = 1,
    ): \Generator {
        $batch = $this->batch();
        $pending = self::iterate($requests);
        $keys = [];

        while (true) {
            while ($batch->pending < $concurrency && $pending->valid()) {
                $keys[] = $pending->key();

                $batch->add(\array_key_last($keys), $pending->current(), $options);

                $pending->next();
            }

            $result = $batch->next();

            if ($result === null) {
                break;
            }

            yield $keys[$result[0]] => $result[1];

            unset($keys[$result[0]]);
        }
    }

    public function batch(): TransportBatchInterface
    {
        return new CurlTransportBatch(
            prepare: $this->prepare(...),
            result: $this->result(...),
        );
    }

    public function stream(
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
    ): ClientResponseInterface|HttpClientException {
        $multi = \curl_multi_init();

        [$handle, $transfer] = $this->prepare($request, $options);

        \curl_multi_add_handle($multi, $handle);

        $result = null;

        while (!$transfer->headersComplete && $result === null) {
            $result = self::pump($multi);
        }

        if ($result !== null) {
            \curl_multi_remove_handle($multi, $handle);
            \curl_multi_close($multi);

            return $this->result($transfer, $handle, $result, $options);
        }

        $content = new Stream(
            streamProxy: new CurlBodyStreamProxy(
                multi: $multi,
                handle: $handle,
                transfer: $transfer,
                pump: static fn (): ?int => self::pump($multi),
                failureOf: fn (int $result): ?HttpClientException => $this->failure($transfer, $handle, $result, $options),
            ),
            autoFlush: true,
        );

        try {
            return $transfer->toResponse(self::info($handle), $this->serializerManager, $content);
            // @codeCoverageIgnoreStart
        } catch (HttpClientException $exception) {
            \curl_multi_remove_handle($multi, $handle);
            \curl_multi_close($multi);

            return $exception;
            // @codeCoverageIgnoreEnd
        }
    }

    private static function pump(
        \CurlMultiHandle $multi,
    ): ?int {
        do {
            $status = \curl_multi_exec($multi, $running);
        } while ($status === \CURLM_CALL_MULTI_PERFORM);

        $message = \curl_multi_info_read($multi);

        if ($message !== false && \is_int($message['result'])) {
            return $message['result'];
        }

        if ($running > 0) {
            \curl_multi_select($multi, 1.0);
        }

        return null;
    }

    /**
     * @template TKey of array-key
     *
     * @param iterable<TKey, ClientRequestInterface> $requests
     * @return \Generator<TKey, ClientRequestInterface>
     */
    private static function iterate(
        iterable $requests,
    ): \Generator {
        yield from $requests;
    }

    /**
     * @return array{0: \CurlHandle, 1: CurlTransfer}
     */
    private function prepare(
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
    ): array {
        $body = $request->body?->encode($this->serializerManager);
        $transfer = new CurlTransfer(
            request: $request,
            upload: $body instanceof StreamInterface
                ? $body
                : null,
            maxResponseSize: $options->maxResponseSize,
            addressCheck: $options->addressCheck,
            progress: $options->progress,
            sink: $options->sink,
        );

        $headers = [];

        foreach ($request->headers as $header) {
            $headers[] = $header->name . ': ' . $header->value;
        }

        if ($request->body !== null && !$request->hasHeader('Content-Type')) {
            $headers[] = 'Content-Type: ' . $request->body->contentType;
        }

        $settings = [
            \CURLOPT_URL => (string) $request->url,
            \CURLOPT_CUSTOMREQUEST => $request->method->name,
            \CURLOPT_HTTPHEADER => $headers,
            \CURLOPT_FOLLOWLOCATION => false,
            \CURLOPT_PROTOCOLS_STR => 'http,https',
            \CURLOPT_ACCEPT_ENCODING => '',
            \CURLOPT_CONNECTTIMEOUT_MS => self::milliseconds($options->connectTimeout),
            \CURLOPT_TIMEOUT_MS => self::milliseconds($options->timeout),
            \CURLOPT_SSL_VERIFYPEER => true,
            \CURLOPT_SSL_VERIFYHOST => 2,
            \CURLOPT_SSLVERSION => \CURL_SSLVERSION_TLSv1_2,
            \CURLOPT_PROXY => $options->proxy !== null
                ? (string) $options->proxy->url
                : '',
            \CURLOPT_HEADERFUNCTION => $transfer->header(...),
            \CURLOPT_WRITEFUNCTION => $transfer->write(...),
        ];

        if ($request->method === Method::HEAD) {
            $settings[\CURLOPT_NOBODY] = true;
        }

        if ($request->method === Method::CONNECT) {
            $settings[\CURLOPT_REQUEST_TARGET] = \sprintf(
                '%s:%d',
                $request->url->host,
                $request->url->port ?? ($request->url->scheme === 'http' ? 80 : 443),
            );
        }

        if (\is_string($body)) {
            $settings[\CURLOPT_POSTFIELDS] = $body;
        } elseif ($body instanceof StreamInterface) {
            $settings[\CURLOPT_UPLOAD] = true;
            $settings[\CURLOPT_READFUNCTION] = $transfer->read(...);

            $size = $body->getSize();

            if ($size !== null) {
                $settings[\CURLOPT_INFILESIZE_LARGE] = $size;
            }
        }

        if ($options->idleTimeout !== null) {
            $settings[\CURLOPT_LOW_SPEED_LIMIT] = 1;
            $settings[\CURLOPT_LOW_SPEED_TIME] = \max(1, $options->idleTimeout->seconds);
        }

        if ($options->proxy !== null) {
            if ($options->proxy->username !== null) {
                $settings[\CURLOPT_PROXYUSERPWD] = $options->proxy->username . ':' . ($options->proxy->password ?? '');
            }

            if (\sizeof($options->proxy->noProxy) > 0) {
                $settings[\CURLOPT_NOPROXY] = \join(',', $options->proxy->noProxy);
            }
        }

        if ($options->clientCertificate !== null) {
            $settings[\CURLOPT_SSLCERT] = $options->clientCertificate->certificate;

            if ($options->clientCertificate->key !== null) {
                $settings[\CURLOPT_SSLKEY] = $options->clientCertificate->key;
            }

            if ($options->clientCertificate->passphrase !== null) {
                $settings[\CURLOPT_KEYPASSWD] = $options->clientCertificate->passphrase;
            }
        }

        if ($options->certificateAuthority !== null) {
            $settings[\CURLOPT_CAINFO] = $options->certificateAuthority;
        }

        if ($options->progress !== null) {
            $settings[\CURLOPT_NOPROGRESS] = false;
            $settings[\CURLOPT_XFERINFOFUNCTION] = $transfer->progress(...);
        }

        if ($options->addressCheck !== null) {
            $settings[\CURLOPT_PREREQFUNCTION] = $transfer->prerequest(...);
        }

        if ($this->share !== null) {
            $settings[\CURLOPT_SHARE] = $this->share;
        }

        $handle = \curl_init();

        \curl_setopt_array($handle, $settings);
        \curl_setopt($handle, \CURLOPT_HTTP_VERSION, self::httpVersion($request->httpVersion ?? $options->httpVersion));

        return [
            $handle,
            $transfer,
        ];
    }

    private function result(
        CurlTransfer $transfer,
        \CurlHandle $handle,
        int $result,
        TransportOptionsInterface $options,
    ): ClientResponseInterface|HttpClientException {
        $transfer->close();

        $failure = $this->failure($transfer, $handle, $result, $options);

        if ($failure !== null) {
            return $failure;
        }

        try {
            return $transfer->toResponse(self::info($handle), $this->serializerManager);
            // @codeCoverageIgnoreStart
        } catch (HttpClientException $exception) {
            return $exception;
        }
        // @codeCoverageIgnoreEnd
    }

    private function failure(
        CurlTransfer $transfer,
        \CurlHandle $handle,
        int $result,
        TransportOptionsInterface $options,
    ): ?HttpClientException {
        if ($transfer->aborted) {
            return HttpClientException::fromAborted($transfer->request);
        }

        if ($transfer->deniedAddress !== null) {
            return HttpClientException::fromPrivateNetwork($transfer->request, $transfer->deniedAddress);
        }

        if ($transfer->unwritable && $transfer->sink !== null) {
            return HttpClientException::fromUnwritableDownload($transfer->request, $transfer->sink);
        }

        if ($transfer->tooLarge) {
            return HttpClientException::fromResponseTooLarge(
                request: $transfer->request,
                limit: (int) $options->maxResponseSize,
            );
        }

        if ($result === \CURLE_OPERATION_TIMEDOUT) {
            return HttpClientException::fromTimeout(
                request: $transfer->request,
                timeout: self::timeout($handle),
            );
        }

        if ($result !== \CURLE_OK) {
            return HttpClientException::fromTransportError(
                request: $transfer->request,
                errorCode: $result,
                error: \curl_error($handle) !== ''
                    ? \curl_error($handle)
                    : \curl_strerror($result) ?? '',
            );
        }

        return null;
    }

    private static function timeout(
        \CurlHandle $handle,
    ): HttpClientTimeout {
        if (\str_contains(\curl_error($handle), 'too slow')) {
            return HttpClientTimeout::IDLE;
        }

        if (\curl_getinfo($handle, \CURLINFO_CONNECT_TIME_T) === 0) {
            return HttpClientTimeout::CONNECT; // @codeCoverageIgnore
        }

        return HttpClientTimeout::TOTAL;
    }

    /**
     * @return array<string, mixed>
     */
    private static function info(
        \CurlHandle $handle,
    ): array {
        return [
            'url' => \curl_getinfo($handle, \CURLINFO_EFFECTIVE_URL),
            'http_code' => \curl_getinfo($handle, \CURLINFO_RESPONSE_CODE),
            'primary_ip' => \curl_getinfo($handle, \CURLINFO_PRIMARY_IP),
            'primary_port' => \curl_getinfo($handle, \CURLINFO_PRIMARY_PORT),
            'num_connects' => \curl_getinfo($handle, \CURLINFO_NUM_CONNECTS),
            'size_upload' => \curl_getinfo($handle, \CURLINFO_SIZE_UPLOAD_T),
            'size_download' => \curl_getinfo($handle, \CURLINFO_SIZE_DOWNLOAD_T),
            'namelookup_time_us' => \curl_getinfo($handle, \CURLINFO_NAMELOOKUP_TIME_T),
            'connect_time_us' => \curl_getinfo($handle, \CURLINFO_CONNECT_TIME_T),
            'appconnect_time_us' => \curl_getinfo($handle, \CURLINFO_APPCONNECT_TIME_T),
            'pretransfer_time_us' => \curl_getinfo($handle, \CURLINFO_PRETRANSFER_TIME_T),
            'starttransfer_time_us' => \curl_getinfo($handle, \CURLINFO_STARTTRANSFER_TIME_T),
            'total_time_us' => \curl_getinfo($handle, \CURLINFO_TOTAL_TIME_T),
        ];
    }

    private static function milliseconds(
        DurationInterface $duration,
    ): int {
        return $duration->seconds * 1000 + \intdiv($duration->nanoseconds, 1_000_000);
    }

    private static function httpVersion(
        ?HttpVersion $httpVersion,
    ): int {
        return match ($httpVersion) {
            HttpVersion::V0_9, HttpVersion::V1_0 => \CURL_HTTP_VERSION_1_0,
            HttpVersion::V1_1 => \CURL_HTTP_VERSION_1_1,
            HttpVersion::V2_0 => \CURL_HTTP_VERSION_2_0,
            HttpVersion::V3_0 => \CURL_HTTP_VERSION_3,
            null => \CURL_HTTP_VERSION_2TLS,
        };
    }
}
