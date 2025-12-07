<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http\Response;

use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;

class Response implements ResponseInterface
{
    /**
     * @param HeaderInterface[] $headers
     */
    final public function __construct(
        public readonly string $body = '',
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
        ResponseCode $responseCode = ResponseCode::OK,
    ): static {
        return new static(
            headers: $headers,
            responseCode: $responseCode,
            body: '',
        )->withHeader(
            header: new Header('Location', $uri),
            replace: true,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    protected function getHeaderIndex(
        array $headers,
        HeaderInterface|string $lookupHeader,
    ): ?int {
        if ($lookupHeader instanceof HeaderInterface) {
            $lookupHeader = $lookupHeader->name;
        }

        foreach ($headers as $index => $header) {
            if ($header->name === $lookupHeader) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return HeaderInterface[]
     */
    private function getReplacementHeaders(
        HeaderInterface ...$headers,
    ): array {
        $newHeaders = $this->headers;

        foreach ($headers as $header) {
            $headerIndex = $this->getHeaderIndex($newHeaders, $header);

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
                    ? $this->getReplacementHeaders(...$headers)
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
        string $body,
    ): static {
        return clone (
            $this,
            [
                'body' => $body,
            ],
        );
    }
}
