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

namespace Tuxxedo\Http\Client\Request;

use Tuxxedo\Http\Client\Body\RequestBodyInterface;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;

interface ClientRequestInterface
{
    public Method $method {
        get;
    }

    public UrlInterface $url {
        get;
    }

    /**
     * @var HeaderInterface[]
     */
    public array $headers {
        get;
    }

    public ?RequestBodyInterface $body {
        get;
    }

    /**
     * @var array<string, mixed>
     */
    public array $query {
        get;
    }

    public ?HttpVersion $httpVersion {
        get;
    }

    public function withMethod(
        Method|string $method,
    ): static;

    /**
     * @throws UrlException
     */
    public function withUrl(
        UrlInterface|string $url,
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

    public function withBody(
        ?RequestBodyInterface $body,
    ): static;

    /**
     * @param array<string, mixed> $query
     */
    public function withQuery(
        array $query,
        bool $replace = false,
    ): static;

    public function withHttpVersion(
        ?HttpVersion $httpVersion,
    ): static;
}
