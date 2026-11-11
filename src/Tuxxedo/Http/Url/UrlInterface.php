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

namespace Tuxxedo\Http\Url;

interface UrlInterface extends \Stringable
{
    public string $scheme {
        get;
    }

    public string $user {
        get;
    }

    public string $password {
        get;
    }

    public string $host {
        get;
    }

    public ?int $port {
        get;
    }

    public string $path {
        get;
    }

    public string $query {
        get;
    }

    public string $fragment {
        get;
    }

    /**
     * @throws UrlException
     */
    public function withScheme(
        string $scheme,
    ): static;

    public function withUserInfo(
        string $user,
        string $password = '',
    ): static;

    /**
     * @throws UrlException
     */
    public function withHost(
        string $host,
    ): static;

    /**
     * @throws UrlException
     */
    public function withPort(
        ?int $port,
    ): static;

    public function withPath(
        string $path,
    ): static;

    /**
     * @param array<array-key, mixed>|string $query
     */
    public function withQuery(
        array|string $query,
    ): static;

    public function withFragment(
        string $fragment,
    ): static;

    /**
     * @throws UrlException
     */
    public function resolve(
        UrlInterface|string $reference,
    ): static;

    /**
     * @throws UrlException
     */
    public function append(
        string $path,
    ): static;
}
