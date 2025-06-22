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
        public readonly array $headers = [],
        public readonly ResponseCode $responseCode = ResponseCode::OK,
        public readonly string $body = '',
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

    public function withHeader(
        HeaderInterface $header,
        bool $replace = false,
    ): static {
        $headers = $this->headers;

        if ($replace) {
            $headerIndex = $this->getHeaderIndex($headers, $header);

            if ($headerIndex !== null) {
                $headers[$headerIndex] = $header;
            } else {
                $headers[] = $header;
            }
        } else {
            $headers[] = $header;
        }

        return new static(
            headers: $headers,
            responseCode: $this->responseCode,
            body: $this->body,
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

        return new static(
            headers: $headers,
            responseCode: $this->responseCode,
            body: $this->body,
        );
    }

    public function withResponseCode(
        ResponseCode $responseCode,
    ): static {
        return new static(
            headers: $this->headers,
            responseCode: $responseCode,
            body: $this->body,
        );
    }

    public function withBody(
        string $body,
    ): static {
        return new static(
            headers: $this->headers,
            responseCode: $this->responseCode,
            body: $body,
        );
    }
}
