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

namespace Tuxxedo\Http\Client\Cache;

use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponse;
use Tuxxedo\Http\Client\ClientResponseInfo;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpVersion;

readonly class CacheEntry implements CacheEntryInterface
{
    public ?string $etag;
    public ?string $lastModified;

    /**
     * @param list<array{0: string, 1: string}> $headers
     * @param array<string, string> $vary
     */
    public function __construct(
        public int $statusCode,
        public HttpVersion $httpVersion,
        public array $headers,
        public string $body,
        public float $freshUntil,
        public array $vary = [],
    ) {
        $this->etag = self::header($headers, 'ETag');
        $this->lastModified = self::header($headers, 'Last-Modified');
    }

    /**
     * @param array<mixed> $data
     */
    public static function tryFromArray(
        array $data,
    ): ?self {
        $headers = [];
        $vary = [];
        $headerData = $data['headers'] ?? [];
        $varyData = $data['vary'] ?? [];

        if (!\is_array($headerData) || !\is_array($varyData)) {
            return null;
        }

        foreach ($headerData as $header) {
            if (\is_array($header) && \is_string($header[0] ?? null) && \is_string($header[1] ?? null)) {
                $headers[] = [
                    $header[0],
                    $header[1],
                ];
            }
        }

        foreach ($varyData as $name => $value) {
            if (\is_string($value)) {
                $vary[(string) $name] = $value;
            }
        }

        $statusCode = $data['statusCode'] ?? null;
        $httpVersion = \is_string($data['httpVersion'] ?? null)
            ? HttpVersion::tryFrom($data['httpVersion'])
            : null;

        $body = \is_string($data['body'] ?? null)
            ? \base64_decode($data['body'], true)
            : false;

        $freshUntil = $data['freshUntil'] ?? null;

        if (!\is_int($statusCode) || $httpVersion === null || $body === false || !\is_numeric($freshUntil)) {
            return null;
        }

        return new self(
            statusCode: $statusCode,
            httpVersion: $httpVersion,
            headers: $headers,
            body: $body,
            freshUntil: (float) $freshUntil,
            vary: $vary,
        );
    }

    public function matches(
        ClientRequestInterface $request,
    ): bool {
        foreach ($this->vary as $name => $value) {
            if (self::requestHeader($request, $name) !== $value) {
                return false;
            }
        }

        return true;
    }

    public function toResponse(
        ClientRequestInterface $request,
    ): ClientResponseInterface {
        $headers = [];

        foreach ($this->headers as [$name, $value]) {
            $headers[] = new Header($name, $value);
        }

        return new ClientResponse(
            content: $this->body,
            headers: $headers,
            statusCode: $this->statusCode,
            httpVersion: $this->httpVersion,
            info: new ClientResponseInfo(
                [
                    'url' => (string) $request->url,
                    'http_code' => $this->statusCode,
                ],
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'statusCode' => $this->statusCode,
            'httpVersion' => $this->httpVersion->value,
            'headers' => $this->headers,
            'body' => \base64_encode($this->body),
            'freshUntil' => $this->freshUntil,
            'vary' => $this->vary,
        ];
    }

    public static function requestHeader(
        ClientRequestInterface $request,
        string $name,
    ): string {
        $values = [];

        foreach ($request->headers as $header) {
            if ($header->is($name)) {
                $values[] = $header->value;
            }
        }

        return \join(', ', $values);
    }

    /**
     * @param list<array{0: string, 1: string}> $headers
     */
    private static function header(
        array $headers,
        string $name,
    ): ?string {
        foreach ($headers as [$header, $value]) {
            if (\strcasecmp($header, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
