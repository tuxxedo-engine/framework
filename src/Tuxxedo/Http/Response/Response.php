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

use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Response\Stream\Stream;
use Tuxxedo\Http\Response\Stream\StreamInterface;

class Response implements ResponseInterface
{
    /**
     * @param HeaderInterface[] $headers
     */
    final public function __construct(
        public readonly StreamInterface|string $body = '',
        public readonly array $headers = [],
        public readonly ResponseCode $responseCode = ResponseCode::OK,
    ) {
    }

    /**
     * @param HeaderInterface[] $headers
     *
     * @throws HttpException
     */
    public static function json(
        mixed $json,
        bool $prettyPrint = false,
        int $flags = 0,
        array $headers = [],
        ResponseCode $responseCode = ResponseCode::OK,
    ): static {
        try {
            if ($prettyPrint) {
                $flags |= \JSON_PRETTY_PRINT;
            }

            $body = \json_encode(
                value: $json,
                flags: $flags | \JSON_THROW_ON_ERROR,
            );
        } catch (\Exception) {
            throw HttpException::fromInternalServerError();
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
    public static function capture(
        \Closure $callback,
        array $headers = [],
        ResponseCode $responseCode = ResponseCode::OK,
    ): static {
        \ob_start();
        $callback();

        $contents = \ob_get_clean();

        if (\is_bool($contents)) {
            throw HttpException::fromInternalServerError();
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
    public static function html(
        string $html,
        array $headers = [],
        ResponseCode $responseCode = ResponseCode::OK,
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
    public static function text(
        string $text,
        array $headers = [],
        ResponseCode $responseCode = ResponseCode::OK,
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
    public static function redirect(
        string $uri,
        array $headers = [],
        ResponseCode $responseCode = ResponseCode::FOUND,
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
     * @param HeaderInterface[] $headers
     */
    public static function empty(
        array $headers = [],
        ResponseCode $responseCode = ResponseCode::OK,
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
    public static function stream(
        mixed $stream,
        bool $autoFlush = false,
        int $chunkSize = 8192,
        array $headers = [],
        ResponseCode $responseCode = ResponseCode::OK,
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
        } elseif ($stream instanceof StreamInterface) {
            $body = $stream;
        }

        return new static(
            headers: $headers,
            responseCode: $responseCode,
            body: $body ?? '',
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

    public function withResponseCode(
        ResponseCode|int $responseCode,
    ): static {
        return clone (
            $this,
            [
                'responseCode' => $responseCode instanceof ResponseCode
                    ? $responseCode
                    : ResponseCode::from($responseCode),
            ],
        );
    }

    public function withBody(
        StreamInterface|string $body,
    ): static {
        // @todo Body cloning may be impossible with streams because they may be resources, sigh

        return clone (
            $this,
            [
                'body' => $body,
            ],
        );
    }
}
