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

readonly class Cookie implements CookieInterface
{
    /**
     * @throws CookieException
     */
    final public function __construct(
        public string $name,
        public string $value,
        public int $expires,
        public string $path = '/',
        public string $domain = '',
        public bool $secure = false,
        public bool $httpOnly = true,
        public SameSite $sameSite = SameSite::LAX,
    ) {
        self::guardSameSite(
            name: $name,
            secure: $secure,
            sameSite: $sameSite,
        );
    }

    public function is(
        string $name,
    ): bool {
        return \strcasecmp($this->name, $name) === 0;
    }

    public function withValue(
        string $value,
    ): static {
        return clone (
            $this,
            [
                'value' => $value,
            ],
        );
    }

    public function withExpires(
        int $expires,
    ): static {
        return clone (
            $this,
            [
                'expires' => $expires,
            ],
        );
    }

    public function withPath(
        string $path,
    ): static {
        return clone (
            $this,
            [
                'path' => $path,
            ],
        );
    }

    public function withDomain(
        string $domain,
    ): static {
        return clone (
            $this,
            [
                'domain' => $domain,
            ],
        );
    }

    /**
     * @throws CookieException
     */
    public function withSecure(
        bool $secure,
    ): static {
        self::guardSameSite(
            name: $this->name,
            secure: $secure,
            sameSite: $this->sameSite,
        );

        return clone (
            $this,
            [
                'secure' => $secure,
            ],
        );
    }

    public function withHttpOnly(
        bool $httpOnly,
    ): static {
        return clone (
            $this,
            [
                'httpOnly' => $httpOnly,
            ],
        );
    }

    /**
     * @throws CookieException
     */
    public function withSameSite(
        SameSite $sameSite,
    ): static {
        self::guardSameSite(
            name: $this->name,
            secure: $this->secure,
            sameSite: $sameSite,
        );

        return clone (
            $this,
            [
                'sameSite' => $sameSite,
            ],
        );
    }

    /**
     * @throws CookieException
     */
    private static function guardSameSite(
        string $name,
        bool $secure,
        SameSite $sameSite,
    ): void {
        if ($sameSite === SameSite::NONE && !$secure) {
            throw CookieException::fromSameSiteNoneRequiresSecure($name);
        }
    }
}
