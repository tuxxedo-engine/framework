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
        return new static(
            name: $this->name,
            value: $value,
            expires: $this->expires,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
        );
    }

    public function withExpires(
        int $expires,
    ): static {
        return new static(
            name: $this->name,
            value: $this->value,
            expires: $expires,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
        );
    }

    public function withPath(
        string $path,
    ): static {
        return new static(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            path: $path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
        );
    }

    public function withDomain(
        string $domain,
    ): static {
        return new static(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            path: $this->path,
            domain: $domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
        );
    }

    public function withSecure(
        bool $secure,
    ): static {
        return new static(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            path: $this->path,
            domain: $this->domain,
            secure: $secure,
            httpOnly: $this->httpOnly,
        );
    }

    public function withHttpOnly(
        bool $httpOnly,
    ): static {
        return new static(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $httpOnly,
        );
    }
}
