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

namespace Tuxxedo\Http\Client\Response;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Cookie;
use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerManagerInterface;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\SystemClock;

class ClientResponse implements ClientResponseInterface
{
    private const array UNFORWARDED_HEADERS = [
        'connection',
        'content-encoding',
        'content-length',
        'keep-alive',
        'proxy-authenticate',
        'proxy-connection',
        'set-cookie',
        'te',
        'trailer',
        'transfer-encoding',
        'upgrade',
    ];

    /**
     * @var Cookie[]|null
     */
    private ?array $cookiesCache = null;

    public readonly ResponseCode $responseCode;
    public readonly ClientHeaderContext $headers;
    public readonly ClientBodyContext $body;
    public readonly ClientResponseInfoInterface $info;
    private readonly InstantInterface $receivedAt;

    /**
     * @var CookieInterface[]
     */
    public array $cookies {
        get {
            if ($this->cookiesCache === null) {
                $this->cookiesCache = [];

                foreach ($this->headers->all() as $header) {
                    if (!$header->is('Set-Cookie')) {
                        continue;
                    }

                    $cookie = Cookie::tryFromSetCookie(
                        header: $header->value,
                        url: $this->info->effectiveUrl,
                        now: $this->receivedAt,
                    );

                    if ($cookie !== null) {
                        $this->cookiesCache[] = $cookie;
                    }
                }
            }

            return $this->cookiesCache;
        }
    }

    public bool $buffered {
        get {
            return \is_string($this->content);
        }
    }

    /**
     * @param HeaderInterface[] $headers
     */
    final public function __construct(
        private readonly StreamInterface|string $content = '',
        array $headers = [],
        public readonly int $statusCode = 200,
        public readonly HttpVersion $httpVersion = HttpVersion::V1_1,
        ?ClientResponseInfoInterface $info = null,
        ?InstantInterface $receivedAt = null,
        ?SerializerManagerInterface $serializerManager = null,
    ) {
        $this->responseCode = ResponseCode::resolve($statusCode);
        $this->headers = new ClientHeaderContext($headers);
        $this->body = new ClientBodyContext(
            serializerManager: $serializerManager,
            body: $content,
            contentType: $this->headers->has('Content-Type')
                ? $this->headers->string('Content-Type')
                : null,
        );

        $this->info = $info ?? new ClientResponseInfo([]);
        $this->receivedAt = $receivedAt ?? new SystemClock()->now();
    }

    /**
     * @param HeaderInterface[] $headers
     */
    public static function json(
        mixed $data,
        int $statusCode = 200,
        array $headers = [],
    ): static {
        return new static(
            content: \json_encode($data, \JSON_THROW_ON_ERROR),
            headers: [
                new Header('Content-Type', 'application/json'),
                ...$headers,
            ],
            statusCode: $statusCode,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    public static function text(
        string $text,
        int $statusCode = 200,
        array $headers = [],
    ): static {
        return new static(
            content: $text,
            headers: [
                new Header('Content-Type', 'text/plain; charset=utf-8'),
                ...$headers,
            ],
            statusCode: $statusCode,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    public static function html(
        string $html,
        int $statusCode = 200,
        array $headers = [],
    ): static {
        return new static(
            content: $html,
            headers: [
                new Header('Content-Type', 'text/html; charset=utf-8'),
                ...$headers,
            ],
            statusCode: $statusCode,
        );
    }

    /**
     * @param HeaderInterface[] $headers
     */
    public static function empty(
        int $statusCode = 204,
        array $headers = [],
    ): static {
        return new static(
            headers: $headers,
            statusCode: $statusCode,
        );
    }

    public function toResponse(
        ContainerInterface $container,
    ): ResponseInterface {
        $headers = [];

        foreach ($this->headers->all() as $header) {
            if (!\in_array(\strtolower($header->name), self::UNFORWARDED_HEADERS, true)) {
                $headers[] = $header;
            }
        }

        return new Response(
            body: $this->content,
            headers: $headers,
            responseCode: $this->responseCode,
            httpVersion: $this->httpVersion,
        );
    }
}
