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

namespace Tuxxedo\Http;

interface CookieInterface extends HeaderInterface
{
    public int $expires {
        get;
    }

    public string $path {
        get;
    }

    public string $domain {
        get;
    }

    public bool $secure {
        get;
    }

    public bool $httpOnly {
        get;
    }

    public function withExpires(
        int $expires,
    ): static;

    public function withPath(
        string $path,
    ): static;

    public function withDomain(
        string $domain,
    ): static;

    public function withSecure(
        bool $secure,
    ): static;

    public function withHttpOnly(
        bool $httpOnly,
    ): static;
}
