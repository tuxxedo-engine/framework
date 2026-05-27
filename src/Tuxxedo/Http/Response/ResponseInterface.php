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
use Tuxxedo\Http\Response\Stream\StreamInterface;

// @todo Implement caching withEtag, withCacheControl, withLastModified etc
// @todo Implement withVary
#[DefaultImplementation(class: Response::class)]
interface ResponseInterface extends ResponseCodeInterface
{
    /**
     * @var HeaderInterface[]
     */
    public array $headers {
        get;
    }

    public StreamInterface|string $body {
        get;
    }

    #[\NoDiscard]
    public function withHeader(
        HeaderInterface $header,
        bool $replace = false,
    ): static;

    /**
     * @param HeaderInterface[] $headers
     */
    #[\NoDiscard]
    public function withHeaders(
        array $headers,
        bool $replace = false,
    ): static;

    #[\NoDiscard]
    public function withoutHeader(
        string $name,
    ): static;

    #[\NoDiscard]
    public function withCookie(
        CookieInterface $cookie,
        bool $replace = false,
    ): static;

    /**
     * @param CookieInterface[] $cookies
     */
    #[\NoDiscard]
    public function withCookies(
        array $cookies,
        bool $replace = false,
    ): static;

    #[\NoDiscard]
    public function withoutCookie(
        string $name,
    ): static;

    #[\NoDiscard]
    public function withResponseCode(
        ResponseCode|int $responseCode,
    ): static;

    #[\NoDiscard]
    public function withBody(
        StreamInterface|string $body,
    ): static;

    #[\NoDiscard]
    public function withDownload(
        string $filename,
    ): static;

    #[\NoDiscard]
    public function withoutDownload(): static;
}
