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

namespace Unit\Http\Client;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\MemoryCookieJar;
use Tuxxedo\Http\Cookie;
use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class MemoryCookieJarTest extends TestCase
{
    private static function jar(): MemoryCookieJar
    {
        return new MemoryCookieJar(
            new FixedClock(Instant::fromUnixTimestamp(1_700_000_000)),
        );
    }

    public function testHostOnlyCookiesMatchTheirHostOnly(): void
    {
        $jar = self::jar();

        $jar->store(
            [
                new Cookie('session', 'abc', 0, httpOnly: false),
            ],
            Url::parse('https://www.example.com/'),
        );

        self::assertSame('session=abc', $jar->header(Url::parse('https://WWW.example.com/account')));
        self::assertNull($jar->header(Url::parse('https://api.www.example.com/')));
        self::assertNull($jar->header(Url::parse('https://example.com/')));
    }

    public function testDomainCookiesMatchSubdomains(): void
    {
        $jar = self::jar();

        $jar->store(
            [
                new Cookie('theme', 'dark', 0, domain: 'example.com'),
            ],
            Url::parse('https://www.example.com/'),
        );

        self::assertSame('theme=dark', $jar->header(Url::parse('https://api.example.com/')));
        self::assertSame('theme=dark', $jar->header(Url::parse('https://example.com')));
        self::assertNull($jar->header(Url::parse('https://notexample.com/')));
    }

    public function testPathAndSecureMatching(): void
    {
        $jar = self::jar();

        $jar->store(
            [
                new Cookie('a', '1', 0, path: '/account'),
                new Cookie('b', '2', 0, path: '/account/', secure: true),
            ],
            Url::parse('https://www.example.com/'),
        );

        self::assertSame('a=1; b=2', $jar->header(Url::parse('https://www.example.com/account/settings')));
        self::assertSame('a=1', $jar->header(Url::parse('https://www.example.com/account')));
        self::assertNull($jar->header(Url::parse('https://www.example.com/accounts')));
        self::assertSame('a=1', $jar->header(Url::parse('http://www.example.com/account/settings')));
    }

    public function testExpiredCookiesArePruned(): void
    {
        $jar = self::jar();

        $jar->store(
            [
                new Cookie('fresh', '1', 1_700_000_060),
                new Cookie('stale', '2', 1_699_999_999),
                new Cookie('removed', '3', 1),
            ],
            Url::parse('https://www.example.com/'),
        );

        self::assertSame(
            [
                'fresh',
            ],
            \array_map(static fn (CookieInterface $cookie): string => $cookie->name, $jar->cookies),
        );
    }

    public function testStoringTheSameCookieReplacesIt(): void
    {
        $jar = self::jar();
        $url = Url::parse('https://www.example.com/');

        $jar->store(
            [
                new Cookie('session', 'old', 0),
            ],
            $url,
        );

        $jar->store(
            [
                new Cookie('session', 'new', 0),
            ],
            $url,
        );

        self::assertSame('session=new', $jar->header($url));
        self::assertCount(1, $jar->cookies);
    }

    public function testClear(): void
    {
        $jar = self::jar();

        $jar->store(
            [
                new Cookie('session', 'abc', 0),
            ],
            Url::parse('https://www.example.com/'),
        );

        $jar->clear();

        self::assertSame([], $jar->cookies);
        self::assertNull($jar->header(Url::parse('https://www.example.com/')));
    }
}
