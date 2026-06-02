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

namespace Tuxxedo\Http\Response;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Response\Stream\JsonStreamFormat;
use Tuxxedo\Http\Response\Stream\SseEventInterface;
use Tuxxedo\Http\Response\Stream\Stream;
use Tuxxedo\Http\Response\Stream\StreamInterface;
use Tuxxedo\Router\RouterInterface;

// @todo Static file response factory?
class Response implements ResponseInterface, ResponsableInterface
{
    /**
     * @var (\Closure(): ResponseInterface)|null
     */
    private ?\Closure $responseResolver = null;

    public readonly ResponseCode $responseCode;

    /**
     * @param HeaderInterface[] $headers
     */
    final public function __construct(
        public readonly StreamInterface|string $body = '',
        public readonly array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ) {
        if (!$responseCode instanceof ResponseCode) {
            $responseCode = ResponseCode::from($responseCode);
        }

        $this->responseCode = $responseCode;
    }

    private function responseResolver(
        ?\Closure $responseResolver,
    ): void {
        $this->responseResolver = $responseResolver;
    }

    public function toResponse(
        ContainerInterface $container,
    ): ResponseInterface {
        if ($this->responseResolver !== null) {
            return $container->call(
                $this->responseResolver,
                [
                    'response' => $this,
                ],
            );
        }

        return $this;
    }

    /**
     * @param HeaderInterface[] $headers
     *
     * @throws HttpException
     */
    #[\NoDiscard]
    public static function json(
        mixed $json,
        bool $prettyPrint = false,
        int $flags = 0,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        try {
            if ($prettyPrint) {
                $flags |= \JSON_PRETTY_PRINT;
            }

            $body = \json_encode(
                value: $json,
                flags: $flags | \JSON_THROW_ON_ERROR,
            );
        } catch (\Exception $exception) {
            throw HttpException::fromInternalServerError(
                exception: $exception,
            );
        }

        return new static(
            headers: $headers,
            responseCode: $responseCode,
            body: $body,
        )->withHeader(
            header: new Header('Content-Type', 'application/json'),
            replace: true,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     *
     * @throws HttpException
     */
    #[\NoDiscard]
    public static function capture(
        \Closure $callback,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        \ob_start();
        $callback();

        $contents = \ob_get_clean();

        if (\is_bool($contents)) {
            throw HttpException::fromInternalServerError(); // @codeCoverageIgnore
        }

        return new static(
            headers: $headers,
            responseCode: $responseCode,
            body: $contents,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function html(
        string $html,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        return new static(
            headers: $headers,
            responseCode: $responseCode,
            body: $html,
        )->withHeader(
            header: new Header('Content-Type', 'text/html'),
            replace: true,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function text(
        string $text,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        return new static(
            headers: $headers,
            responseCode: $responseCode,
            body: $text,
        )->withHeader(
            header: new Header('Content-Type', 'text/plain'),
            replace: true,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function download(
        StreamInterface|string $body,
        string $filename,
        string $contentType = 'application/octet-stream',
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        $bodyHeaders = $body instanceof StreamInterface
            ? $body->headers
            : [];

        return new static(
            headers: $bodyHeaders,
            responseCode: $responseCode,
            body: $body,
        )->withHeaders(
            headers: $headers,
            replace: \sizeof($headers) > 0,
        )->withHeader(
            header: new Header('Content-Type', $contentType),
            replace: true,
        )->withDownload(
            filename: $filename,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function redirect(
        string $uri,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::FOUND,
        string $body = '',
    ): static {
        return new static(
            headers: $headers,
            responseCode: $responseCode,
            body: $body,
        )->withHeader(
            header: new Header('Location', $uri),
            replace: true,
        );
    }

    /**
     * @param array<string, scalar> $arguments
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function redirectRoute(
        string $name,
        array $arguments = [],
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::FOUND,
        string $body = '',
    ): static {
        $response = new static(
            headers: $headers,
            responseCode: $responseCode,
            body: $body,
        );

        $response->responseResolver(
            function (ResponseInterface $response, RouterInterface $router) use ($name, $arguments): ResponseInterface {
                $route = $router->findByName($name, \array_map(\strval(...), $arguments)) ?? throw HttpException::fromInternalServerError();

                return $response->withHeader(
                    header: new Header('Location', $route->asUrl() ?? throw HttpException::fromInternalServerError()),
                    replace: true,
                );
            },
        );

        return $response;
    }

    /**
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function empty(
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        return new static(
            headers: $headers,
            responseCode: $responseCode,
            body: '',
        );
    }

    /**
     * @param \Closure(): \Generator<string>|\Generator<string>|resource|StreamInterface $stream
     * @param positive-int $chunkSize
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function stream(
        mixed $stream,
        bool $autoFlush = false,
        int $chunkSize = 8192,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        if ($stream instanceof \Closure || $stream instanceof \Generator) {
            $body = Stream::fromGenerator(
                generator: $stream,
                autoFlush: $autoFlush,
            );
        } elseif (\is_resource($stream)) {
            $body = Stream::fromResource(
                resource: $stream,
                autoFlush: $autoFlush,
                chunkSize: $chunkSize,
            );
        } else {
            /** @var StreamInterface $body */
            $body = $stream;
        }

        return new static(
            headers: $body->headers,
            responseCode: $responseCode,
            body: $body,
        )->withHeaders(
            headers: $headers,
            replace: \sizeof($headers) > 0,
        );
    }

    /**
     * @param \Closure(): \Generator<mixed>|\Generator<mixed> $stream
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function streamJson(
        \Closure|\Generator $stream,
        JsonStreamFormat $format = JsonStreamFormat::JSONL,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        $body = Stream::fromJson(
            generator: $stream,
            format: $format,
        );

        return new static(
            headers: $body->headers,
            responseCode: $responseCode,
            body: $body,
        )->withHeaders(
            headers: $headers,
            replace: \sizeof($headers) > 0,
        );
    }

    /**
     * @param \Closure(): \Generator<scalar[]>|\Generator<scalar[]> $generator
     * @param string[]|null $columns
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function streamCsv(
        \Closure|\Generator $generator,
        string $separator = ',',
        string $enclosure = '"',
        string $eol = "\n",
        ?array $columns = null,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        $body = Stream::fromCsv(
            generator: $generator,
            separator: $separator,
            enclosure: $enclosure,
            eol: $eol,
            columns: $columns,
        );

        return new static(
            headers: $body->headers,
            responseCode: $responseCode,
            body: $body,
        )->withHeaders(
            headers: $headers,
            replace: \sizeof($headers) > 0,
        );
    }

    /**
     * @param \Closure(): \Generator<SseEventInterface>|\Generator<SseEventInterface> $generator
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public static function streamSse(
        \Closure|\Generator $generator,
        array $headers = [],
        ResponseCode|int $responseCode = ResponseCode::OK,
    ): static {
        $body = Stream::fromSse(
            generator: $generator,
        );

        return new static(
            headers: $body->headers,
            responseCode: $responseCode,
            body: $body,
        )->withHeaders(
            headers: $headers,
            replace: \sizeof($headers) > 0,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    protected function getHeaderIndex(
        array $headers,
        HeaderInterface|string $lookupHeader,
        bool $onlyCookies = false,
    ): ?int {
        if ($lookupHeader instanceof HeaderInterface) {
            $lookupHeader = $lookupHeader->name;
        }

        foreach ($headers as $index => $header) {
            if ($onlyCookies && !$header instanceof CookieInterface) {
                continue;
            }

            if (\strcasecmp($header->name, $lookupHeader) === 0) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param HeaderInterface|HeaderInterface[] $headers
     *
     * @return HeaderInterface[]
     */
    private function getReplacementHeaders(
        HeaderInterface|array $headers,
        bool $onlyCookies = false,
    ): array {
        $newHeaders = $this->headers;

        if (!\is_array($headers)) {
            $headers = [
                $headers,
            ];
        }

        foreach ($headers as $header) {
            $headerIndex = $this->getHeaderIndex($newHeaders, $header, $onlyCookies);

            if ($headerIndex !== null) {
                $newHeaders[$headerIndex] = $header;
            } else {
                $newHeaders[] = $header;
            }
        }

        return $newHeaders;
    }

    public function hasHeader(
        string $name,
    ): bool {
        return $this->getHeaderIndex($this->headers, $name) !== null;
    }

    #[\NoDiscard]
    public function withHeader(
        HeaderInterface $header,
        bool $replace = false,
    ): static {
        return clone (
            $this,
            [
                'headers' => $replace
                    ? $this->getReplacementHeaders($header)
                    : \array_merge(
                        $this->headers,
                        [
                            $header,
                        ],
                    ),
            ],
        );
    }

    #[\NoDiscard]
    public function withHeaders(
        array $headers,
        bool $replace = false,
    ): static {
        return clone (
            $this,
            [
                'headers' => $replace
                    ? $this->getReplacementHeaders($headers)
                    : \array_merge(
                        $this->headers,
                        $headers,
                    ),
            ],
        );
    }

    #[\NoDiscard]
    public function withoutHeader(
        string $name,
    ): static {
        $headers = $this->headers;
        $index = $this->getHeaderIndex($headers, $name);

        if ($index !== null) {
            unset($headers[$index]);
        }

        return clone (
            $this,
            [
                'headers' => $headers,
            ],
        );
    }

    public function hasCookie(
        string $name,
    ): bool {
        return $this->getHeaderIndex($this->headers, $name, onlyCookies: true) !== null;
    }

    #[\NoDiscard]
    public function withCookie(
        CookieInterface $cookie,
        bool $replace = false,
    ): static {
        return clone (
            $this,
            [
                'headers' => $replace
                    ? $this->getReplacementHeaders($cookie, onlyCookies: true)
                    : \array_merge(
                        $this->headers,
                        [
                            $cookie,
                        ],
                    ),
            ],
        );
    }

    #[\NoDiscard]
    public function withCookies(
        array $cookies,
        bool $replace = false,
    ): static {
        return clone (
            $this,
            [
                'headers' => $replace
                    ? $this->getReplacementHeaders($cookies, onlyCookies: true)
                    : \array_merge(
                        $this->headers,
                        $cookies,
                    ),
            ],
        );
    }

    #[\NoDiscard]
    public function withoutCookie(
        string $name,
    ): static {
        $headers = $this->headers;
        $index = $this->getHeaderIndex($headers, $name, onlyCookies: true);

        if ($index !== null) {
            unset($headers[$index]);
        }

        return clone (
            $this,
            [
                'headers' => $headers,
            ],
        );
    }

    #[\NoDiscard]
    public function withResponseCode(
        ResponseCode|int $responseCode,
    ): static {
        return clone (
            $this,
            [
                'responseCode' => !$responseCode instanceof ResponseCode
                    ? ResponseCode::from($responseCode)
                    : $responseCode,
            ],
        );
    }

    #[\NoDiscard]
    public function withBody(
        StreamInterface|string $body,
    ): static {
        return clone (
            $this,
            [
                'body' => $body,
            ],
        );
    }

    #[\NoDiscard]
    public function withDownload(
        string $filename,
    ): static {
        $filename = \str_replace(['/', '\\', "\0"], '', $filename);
        $hasNonAscii = \preg_match('/[^\x20-\x7E]/', $filename) === 1;
        $asciiFallback = $hasNonAscii
            ? (\preg_replace('/[^\x20-\x7E]/', '_', $filename) ?? '')
            : $filename;

        $asciiFallback = \str_replace('"', '\\"', $asciiFallback);
        $disposition = 'attachment; filename="' . $asciiFallback . '"';

        if ($hasNonAscii) {
            $disposition .= "; filename*=UTF-8''" . \rawurlencode($filename);
        }

        return $this->withHeader(
            header: new Header('Content-Disposition', $disposition),
            replace: true,
        );
    }

    #[\NoDiscard]
    public function withoutDownload(): static
    {
        return $this->withoutHeader(
            name: 'Content-Disposition',
        );
    }

    #[\NoDiscard]
    public function withVary(
        string ...$headers,
    ): static {
        if (\sizeof($headers) === 0) {
            return clone $this;
        }

        $merged = $this->mergeVaryEntries($this->parseVaryEntries(), $headers);

        return $this->withHeader(
            header: new Header('Vary', \join(', ', $merged)),
            replace: true,
        );
    }

    #[\NoDiscard]
    public function withoutVary(
        string ...$headers,
    ): static {
        if (\sizeof($headers) === 0) {
            return $this->withoutHeader(
                name: 'Vary',
            );
        }

        $remaining = [];

        foreach ($this->parseVaryEntries() as $entry) {
            $keep = true;

            foreach ($headers as $remove) {
                if (\strcasecmp($entry, $remove) === 0) {
                    $keep = false;

                    break;
                }
            }

            if ($keep) {
                $remaining[] = $entry;
            }
        }

        if (\sizeof($remaining) === 0) {
            return $this->withoutHeader(
                name: 'Vary',
            );
        }

        return $this->withHeader(
            header: new Header('Vary', \join(', ', $remaining)),
            replace: true,
        );
    }

    /**
     * @return string[]
     */
    private function parseVaryEntries(): array
    {
        foreach ($this->headers as $header) {
            if (\strcasecmp($header->name, 'Vary') !== 0) {
                continue;
            }

            $entries = [];

            foreach (\explode(',', $header->value) as $part) {
                $part = \trim($part);

                if ($part !== '') {
                    $entries[] = $part;
                }
            }

            return $entries;
        }

        return [];
    }

    /**
     * @param string[] $existing
     * @param string[] $new
     * @return string[]
     */
    private function mergeVaryEntries(
        array $existing,
        array $new,
    ): array {
        $result = $existing;

        foreach ($new as $entry) {
            $found = false;

            foreach ($result as $existingEntry) {
                if (\strcasecmp($existingEntry, $entry) === 0) {
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    #[\NoDiscard]
    public function withEtag(
        string $etag,
        bool $weak = false,
    ): static {
        $value = '"' . $etag . '"';

        if ($weak) {
            $value = 'W/' . $value;
        }

        return $this->withHeader(
            header: new Header('ETag', $value),
            replace: true,
        );
    }

    #[\NoDiscard]
    public function withLastModified(
        \DateTimeInterface $when,
    ): static {
        $utc = \DateTimeImmutable::createFromInterface($when)
            ->setTimezone(new \DateTimeZone('UTC'));

        return $this->withHeader(
            header: new Header('Last-Modified', $utc->format('D, d M Y H:i:s \G\M\T')),
            replace: true,
        );
    }

    /**
     * @throws HttpException
     */
    #[\NoDiscard]
    public function withCacheControl(
        ?int $maxAge = null,
        ?int $sMaxAge = null,
        bool $public = false,
        bool $private = false,
        bool $noCache = false,
        bool $noStore = false,
        bool $mustRevalidate = false,
        bool $proxyRevalidate = false,
        bool $immutable = false,
        ?int $staleWhileRevalidate = null,
        ?int $staleIfError = null,
    ): static {
        if ($public && $private) {
            throw HttpException::fromInternalServerError();
        }

        $directives = [];

        if ($public) {
            $directives[] = 'public';
        }

        if ($private) {
            $directives[] = 'private';
        }

        if ($noCache) {
            $directives[] = 'no-cache';
        }

        if ($noStore) {
            $directives[] = 'no-store';
        }

        if ($mustRevalidate) {
            $directives[] = 'must-revalidate';
        }

        if ($proxyRevalidate) {
            $directives[] = 'proxy-revalidate';
        }

        if ($immutable) {
            $directives[] = 'immutable';
        }

        if ($maxAge !== null) {
            $directives[] = 'max-age=' . $maxAge;
        }

        if ($sMaxAge !== null) {
            $directives[] = 's-maxage=' . $sMaxAge;
        }

        if ($staleWhileRevalidate !== null) {
            $directives[] = 'stale-while-revalidate=' . $staleWhileRevalidate;
        }

        if ($staleIfError !== null) {
            $directives[] = 'stale-if-error=' . $staleIfError;
        }

        if (\sizeof($directives) === 0) {
            return $this->withoutHeader(
                name: 'Cache-Control',
            );
        }

        return $this->withHeader(
            header: new Header('Cache-Control', \join(', ', $directives)),
            replace: true,
        );
    }
}
