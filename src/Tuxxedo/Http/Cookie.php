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

use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Temporal\InstantInterface;

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

    public static function tryFromSetCookie(
        string $header,
        UrlInterface $url,
        InstantInterface $now,
    ): ?static {
        $parts = \explode(';', $header);
        $pair = \array_shift($parts);
        $equals = \strpos($pair, '=');

        if ($equals === false) {
            return null;
        }

        $name = \trim(\substr($pair, 0, $equals));
        $value = \trim(\substr($pair, $equals + 1));

        if (\preg_match('/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/', $name) !== 1) {
            return null;
        }

        $attributes = [];

        foreach ($parts as $part) {
            $equals = \strpos($part, '=');

            if ($equals === false) {
                $attributes[\strtolower(\trim($part))] = '';

                continue;
            }

            $attributes[\strtolower(\trim(\substr($part, 0, $equals)))] = \trim(\substr($part, $equals + 1));
        }

        $host = \strtolower($url->host);
        $domain = \strtolower(\ltrim($attributes['domain'] ?? '', '.'));

        if ($domain !== '' && $domain !== $host && !\str_ends_with($host, '.' . $domain)) {
            return null;
        }

        $path = $attributes['path'] ?? '';

        if (!\str_starts_with($path, '/')) {
            $path = self::defaultPath($url->path);
        }

        try {
            return new static(
                name: $name,
                value: $value,
                expires: self::expires($attributes, $now),
                path: $path,
                domain: $domain,
                secure: \array_key_exists('secure', $attributes),
                httpOnly: \array_key_exists('httponly', $attributes),
                sameSite: SameSite::tryFrom(\ucfirst(\strtolower($attributes['samesite'] ?? ''))) ?? SameSite::LAX,
            );
        } catch (CookieException) {
            return null;
        }
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
     * @param array<string, string> $attributes
     */
    private static function expires(
        array $attributes,
        InstantInterface $now,
    ): int {
        $maxAge = $attributes['max-age'] ?? null;

        if ($maxAge !== null && \preg_match('/^-?\d+$/', $maxAge) === 1) {
            return (int) $maxAge > 0
                ? $now->toUnixTimestamp() + (int) $maxAge
                : 1;
        }

        $expires = $attributes['expires'] ?? null;

        if ($expires === null) {
            return 0;
        }

        foreach (
            [
                'D, d M Y H:i:s \G\M\T',
                'D, d-M-Y H:i:s \G\M\T',
            ] as $format
        ) {
            $date = \DateTimeImmutable::createFromFormat(
                format: $format,
                datetime: $expires,
                timezone: new \DateTimeZone('UTC'),
            );

            if ($date !== false) {
                return \max(1, $date->getTimestamp());
            }
        }

        return 0;
    }

    private static function defaultPath(
        string $path,
    ): string {
        $slash = \strrpos($path, '/');

        if (!\str_starts_with($path, '/') || $slash === 0 || $slash === false) {
            return '/';
        }

        return \substr($path, 0, $slash);
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
