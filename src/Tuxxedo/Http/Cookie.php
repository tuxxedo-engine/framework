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

readonly class Cookie implements CookieInterface
{
    final public function __construct(
        public string $name,
        public string $value,
        public int $expires,
        public string $path = '/',
        public string $domain = '',
        public bool $secure = false,
        public bool $httpOnly = true,
    ) {
    }

    public function withValue(
        string $value,
    ): static {
        return clone(
            $this,
            [
                'value' => $value,
            ],
        );
    }

    public function withExpires(
        int $expires,
    ): static {
        return clone(
            $this,
            [
                'expires' => $expires,
            ],
        );
    }

    public function withPath(
        string $path,
    ): static {
        return clone(
            $this,
            [
                'path' => $path,
            ],
        );
    }

    public function withDomain(
        string $domain,
    ): static {
        return clone(
            $this,
            [
                'domain' => $domain,
            ],
        );
    }

    public function withSecure(
        bool $secure,
    ): static {
        return clone(
            $this,
            [
                'secure' => $secure,
            ],
        );
    }

    public function withHttpOnly(
        bool $httpOnly,
    ): static {
        return clone(
            $this,
            [
                'httpOnly' => $httpOnly,
            ],
        );
    }
}
