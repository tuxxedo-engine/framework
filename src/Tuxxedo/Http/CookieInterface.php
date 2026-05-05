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

    #[\NoDiscard]
    public function withExpires(
        int $expires,
    ): static;

    #[\NoDiscard]
    public function withPath(
        string $path,
    ): static;

    #[\NoDiscard]
    public function withDomain(
        string $domain,
    ): static;

    #[\NoDiscard]
    public function withSecure(
        bool $secure,
    ): static;

    #[\NoDiscard]
    public function withHttpOnly(
        bool $httpOnly,
    ): static;
}
