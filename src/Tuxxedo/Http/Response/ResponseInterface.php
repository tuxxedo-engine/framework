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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Response\Stream\StreamInterface;

#[DefaultImplementation(class: Response::class)]
interface ResponseInterface extends ResponseCodeInterface
{
    public HttpVersion $httpVersion {
        get;
    }

    /**
     * @var HeaderInterface[]
     */
    public array $headers {
        get;
    }

    public StreamInterface|string $body {
        get;
    }

    public function withHttpVersion(
        HttpVersion $httpVersion,
    ): static;

    public function hasHeader(
        string $name,
    ): bool;

    public function withHeader(
        HeaderInterface $header,
        bool $replace = false,
    ): static;

    /**
     * @param HeaderInterface[] $headers
     */
    public function withHeaders(
        array $headers,
        bool $replace = false,
    ): static;

    public function withoutHeader(
        string $name,
    ): static;

    public function hasCookie(
        string $name,
    ): bool;

    public function withCookie(
        CookieInterface $cookie,
        bool $replace = false,
    ): static;

    /**
     * @param CookieInterface[] $cookies
     */
    public function withCookies(
        array $cookies,
        bool $replace = false,
    ): static;

    public function withoutCookie(
        string $name,
    ): static;

    public function withResponseCode(
        ResponseCode|int $responseCode,
    ): static;

    public function withBody(
        StreamInterface|string $body,
    ): static;

    public function withDownload(
        string $filename,
    ): static;

    public function withoutDownload(): static;

    public function withVary(
        string ...$headers,
    ): static;

    public function withoutVary(
        string ...$headers,
    ): static;

    public function withEtag(
        string $etag,
        bool $weak = false,
    ): static;

    public function withLastModified(
        \DateTimeInterface $when,
    ): static;

    /**
     * @throws HttpException
     */
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
    ): static;
}
