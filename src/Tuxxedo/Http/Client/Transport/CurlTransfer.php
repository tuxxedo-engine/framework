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

use Tuxxedo\Http\Client\ClientProgress;
use Tuxxedo\Http\Client\ClientProgressInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponse;
use Tuxxedo\Http\Client\Response\ClientResponseInfo;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\StatusLine;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerManagerInterface;

class CurlTransfer
{
    /**
     * @var list<string>
     */
    private array $headerLines = [];

    private string $body = '';
    private int $written = 0;

    /**
     * @var resource|false|null
     */
    private mixed $file = null;
    private string $uploadBuffer = '';

    public private(set) bool $tooLarge = false;
    public private(set) bool $headersComplete = false;
    public private(set) ?string $deniedAddress = null;
    public private(set) bool $aborted = false;
    public private(set) bool $unwritable = false;

    /**
     * @var array{0: int, 1: int, 2: int, 3: int}|null
     */
    private ?array $lastProgress = null;

    /**
     * @param (\Closure(string): bool)|null $addressCheck
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     */
    public function __construct(
        public readonly ClientRequestInterface $request,
        private readonly ?StreamInterface $upload = null,
        private readonly ?int $maxResponseSize = null,
        private readonly ?\Closure $addressCheck = null,
        private readonly ?\Closure $progress = null,
        public readonly ?string $sink = null,
    ) {
        if ($sink !== null) {
            $this->file = @\fopen($sink, 'wb');
            $this->unwritable = $this->file === false;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        if (\is_resource($this->file)) {
            \fclose($this->file);
        }

        $this->file = null;
    }

    public function header(
        \CurlHandle $handle,
        string $line,
    ): int {
        if (\str_starts_with($line, 'HTTP/')) {
            $this->headerLines = [];
        }

        $trimmed = \rtrim($line, "\r\n");

        if ($trimmed !== '') {
            $this->headerLines[] = $trimmed;
        } elseif (\preg_match('#^HTTP/\S+ 1\d\d#', $this->headerLines[0] ?? '') !== 1) {
            $this->headersComplete = true;
        }

        return \strlen($line);
    }

    public function write(
        \CurlHandle $handle,
        string $data,
    ): int {
        if (
            $this->maxResponseSize !== null &&
            $this->written + \strlen($data) > $this->maxResponseSize
        ) {
            $this->tooLarge = true;

            return 0;
        }

        $this->headersComplete = true;

        if ($this->sink === null) {
            $this->body .= $data;
            $this->written += \strlen($data);

            return \strlen($data);
        }

        if (!\is_resource($this->file) || \fwrite($this->file, $data) !== \strlen($data)) {
            $this->unwritable = true;

            return 0;
        }

        $this->written += \strlen($data);

        return \strlen($data);
    }

    public function drain(): string
    {
        $body = $this->body;
        $this->body = '';

        return $body;
    }

    public function read(
        \CurlHandle $handle,
        mixed $resource,
        int $length,
    ): string {
        while (
            $this->upload !== null &&
            \strlen($this->uploadBuffer) < $length &&
            ($chunk = $this->upload->read()) !== null
        ) {
            $this->uploadBuffer .= $chunk;
        }

        $data = \substr($this->uploadBuffer, 0, $length);
        $this->uploadBuffer = \substr($this->uploadBuffer, \strlen($data));

        return $data;
    }

    public function progress(
        \CurlHandle $handle,
        int $downloadTotal,
        int $downloaded,
        int $uploadTotal,
        int $uploaded,
    ): int {
        $current = [
            $downloadTotal,
            $downloaded,
            $uploadTotal,
            $uploaded,
        ];

        if ($this->progress === null || $current === $this->lastProgress) {
            return 0;
        }

        $this->lastProgress = $current;

        $continue = ($this->progress)(
            new ClientProgress(
                request: $this->request,
                downloaded: $downloaded,
                downloadTotal: $downloadTotal > 0
                    ? $downloadTotal
                    : null,
                uploaded: $uploaded,
                uploadTotal: $uploadTotal > 0
                    ? $uploadTotal
                    : null,
            ),
        );

        if ($continue === false) {
            $this->aborted = true;

            return 1;
        }

        return 0;
    }

    public function prerequest(
        \CurlHandle $handle,
        string $primaryIp,
        string $localIp,
        int $primaryPort,
        int $localPort,
    ): int {
        if ($this->addressCheck !== null && !($this->addressCheck)($primaryIp)) {
            $this->deniedAddress = $primaryIp;

            return \CURL_PREREQFUNC_ABORT;
        }

        return \CURL_PREREQFUNC_OK;
    }

    /**
     * @param array<string, mixed> $info
     *
     * @throws HttpClientException
     */
    public function toResponse(
        array $info,
        SerializerManagerInterface $serializerManager,
        ?StreamInterface $content = null,
    ): ClientResponseInterface {
        if (\sizeof($this->headerLines) === 0) {
            throw HttpClientException::fromMissingStatusLine($this->request);
        }

        try {
            $headers = [];
            $statusLine = StatusLine::parse($this->headerLines[0]);

            foreach (\array_slice($this->headerLines, 1) as $line) {
                $headers[] = Header::parse($line);
            }
        } catch (HttpException) {
            throw HttpClientException::fromMalformedResponseHead($this->request);
        }

        return new ClientResponse(
            content: $content ?? $this->body,
            headers: $headers,
            statusCode: $statusLine->statusCode,
            httpVersion: $statusLine->httpVersion,
            info: new ClientResponseInfo($info),
            serializerManager: $serializerManager,
        );
    }
}
