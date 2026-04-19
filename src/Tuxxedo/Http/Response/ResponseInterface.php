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
}
