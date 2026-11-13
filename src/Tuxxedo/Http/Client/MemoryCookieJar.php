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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\SystemClock;

class MemoryCookieJar implements CookieJarInterface
{
    /**
     * @var array<string, array{0: string, 1: CookieInterface}>
     */
    private array $entries = [];

    private readonly ClockInterface $clock;

    /**
     * @var CookieInterface[]
     */
    public array $cookies {
        get {
            $this->prune();

            $cookies = [];

            foreach ($this->entries as [, $cookie]) {
                $cookies[] = $cookie;
            }

            return $cookies;
        }
    }

    public function __construct(
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    public function store(
        array $cookies,
        UrlInterface $url,
    ): void {
        $host = \strtolower($url->host);

        foreach ($cookies as $cookie) {
            $origin = $cookie->domain !== ''
                ? $cookie->domain
                : $host;

            $this->entries[$origin . ';' . $cookie->path . ';' . $cookie->name] = [
                $origin,
                $cookie,
            ];
        }

        $this->prune();
    }

    public function header(
        UrlInterface $url,
    ): ?string {
        $this->prune();

        $host = \strtolower($url->host);
        $path = $url->path !== ''
            ? $url->path
            : '/';

        $pairs = [];

        foreach ($this->entries as [$origin, $cookie]) {
            if (
                !self::domainMatches($host, $origin, $cookie->domain === '') ||
                !self::pathMatches($path, $cookie->path) ||
                ($cookie->secure && $url->scheme !== 'https')
            ) {
                continue;
            }

            $pairs[] = $cookie->name . '=' . $cookie->value;
        }

        return \sizeof($pairs) > 0
            ? \join('; ', $pairs)
            : null;
    }

    public function clear(): void
    {
        $this->entries = [];
    }

    private function prune(): void
    {
        $now = $this->clock->now()->toUnixTimestamp();

        foreach ($this->entries as $key => [, $cookie]) {
            if ($cookie->expires !== 0 && $cookie->expires <= $now) {
                unset($this->entries[$key]);
            }
        }
    }

    private static function domainMatches(
        string $host,
        string $origin,
        bool $hostOnly,
    ): bool {
        if ($host === $origin) {
            return true;
        }

        return !$hostOnly && \str_ends_with($host, '.' . $origin);
    }

    private static function pathMatches(
        string $path,
        string $cookiePath,
    ): bool {
        if ($path === $cookiePath || $cookiePath === '/') {
            return true;
        }

        return \str_starts_with($path, \rtrim($cookiePath, '/') . '/');
    }
}
