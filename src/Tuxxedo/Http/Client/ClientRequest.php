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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Http\Client\Body\RawBody;
use Tuxxedo\Http\Client\Body\RequestBodyInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;

readonly class ClientRequest implements ClientRequestInterface
{
    private const array UNFORWARDED_HEADERS = [
        'connection',
        'content-length',
        'cookie',
        'host',
        'keep-alive',
        'proxy-authenticate',
        'proxy-authorization',
        'proxy-connection',
        'te',
        'trailer',
        'transfer-encoding',
        'upgrade',
    ];

    public Method $method;
    public UrlInterface $url;

    /**
     * @param HeaderInterface[] $headers
     * @param array<string, mixed> $query
     *
     * @throws UrlException
     */
    final public function __construct(
        Method|string $method,
        UrlInterface|string $url,
        public array $headers = [],
        public ?RequestBodyInterface $body = null,
        public array $query = [],
        public ?HttpVersion $httpVersion = null,
    ) {
        $this->method = $method instanceof Method
            ? $method
            : Method::from($method);

        $this->url = $url instanceof UrlInterface
            ? $url
            : Url::parse($url);
    }

    /**
     * @throws HttpException
     * @throws UrlException
     */
    public static function fromRequest(
        RequestInterface $request,
        UrlInterface|string $url,
        bool $forwardCookies = false,
    ): static {
        $headers = [];

        foreach ($request->headers->all() as $header) {
            if (!\in_array(\strtolower($header->name), self::UNFORWARDED_HEADERS, true)) {
                $headers[] = $header;
            }
        }

        if ($forwardCookies) {
            $cookies = [];

            foreach ($request->cookies->keys() as $name) {
                $cookies[] = $name . '=' . $request->cookies->string($name);
            }

            if (\sizeof($cookies) > 0) {
                $headers[] = new Header('Cookie', \join('; ', $cookies));
            }
        }

        return new static(
            method: $request->method,
            url: $url,
            headers: $headers,
            body: new RawBody(
                body: $request->body->getStream(),
                contentType: $request->headers->has('Content-Type')
                    ? $request->headers->string('Content-Type')
                    : 'application/octet-stream',
            ),
        );
    }

    public function withMethod(
        Method|string $method,
    ): static {
        return clone (
            $this,
            [
                'method' => $method instanceof Method
                    ? $method
                    : Method::from($method),
            ],
        );
    }

    public function withUrl(
        UrlInterface|string $url,
    ): static {
        return clone (
            $this,
            [
                'url' => $url instanceof UrlInterface
                    ? $url
                    : Url::parse($url),
            ],
        );
    }

    public function hasHeader(
        string $name,
    ): bool {
        foreach ($this->headers as $header) {
            if ($header->is($name)) {
                return true;
            }
        }

        return false;
    }

    public function withHeader(
        HeaderInterface $header,
        bool $replace = false,
    ): static {
        return $this->withHeaders(
            headers: [
                $header,
            ],
            replace: $replace,
        );
    }

    public function withHeaders(
        array $headers,
        bool $replace = false,
    ): static {
        $current = $this->headers;

        if ($replace) {
            foreach ($headers as $header) {
                $current = self::without($current, $header->name);
            }
        }

        return clone (
            $this,
            [
                'headers' => \array_merge(
                    $current,
                    \array_values($headers),
                ),
            ],
        );
    }

    public function withoutHeader(
        string $name,
    ): static {
        return clone (
            $this,
            [
                'headers' => self::without($this->headers, $name),
            ],
        );
    }

    public function withBody(
        ?RequestBodyInterface $body,
    ): static {
        return clone (
            $this,
            [
                'body' => $body,
            ],
        );
    }

    public function withQuery(
        array $query,
        bool $replace = false,
    ): static {
        return clone (
            $this,
            [
                'query' => $replace
                    ? $query
                    : \array_replace($this->query, $query),
            ],
        );
    }

    public function withHttpVersion(
        ?HttpVersion $httpVersion,
    ): static {
        return clone (
            $this,
            [
                'httpVersion' => $httpVersion,
            ],
        );
    }

    /**
     * @param HeaderInterface[] $headers
     * @return list<HeaderInterface>
     */
    private static function without(
        array $headers,
        string $name,
    ): array {
        $remaining = [];

        foreach ($headers as $header) {
            if (!$header->is($name)) {
                $remaining[] = $header;
            }
        }

        return $remaining;
    }
}
