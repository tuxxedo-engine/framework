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

namespace Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Cookie;
use Tuxxedo\Http\CookieException;
use Tuxxedo\Http\SameSite;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Temporal\Instant;

class CookieTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);

        self::assertSame('session', $cookie->name);
        self::assertSame('abc123', $cookie->value);
        self::assertSame(0, $cookie->expires);
        self::assertSame('/', $cookie->path);
        self::assertSame('', $cookie->domain);
        self::assertFalse($cookie->secure);
        self::assertTrue($cookie->httpOnly);
        self::assertSame(SameSite::LAX, $cookie->sameSite);
    }

    public function testConstructorExplicit(): void
    {
        $cookie = new Cookie(
            name: 'session',
            value: 'abc123',
            expires: 9999,
            path: '/admin',
            domain: 'example.com',
            secure: true,
            httpOnly: false,
            sameSite: SameSite::STRICT,
        );

        self::assertSame('/admin', $cookie->path);
        self::assertSame('example.com', $cookie->domain);
        self::assertTrue($cookie->secure);
        self::assertFalse($cookie->httpOnly);
        self::assertSame(SameSite::STRICT, $cookie->sameSite);
    }

    public function testIs(): void
    {
        $cookie = new Cookie(
            name: 'abc',
            value: 'def',
            expires: 1800,
        );

        self::assertTrue($cookie->is('ABC'));
        self::assertFalse($cookie->name === 'ABC');
    }

    public function testWithValue(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);
        $updated = $cookie->withValue('xyz789');

        self::assertNotSame($cookie, $updated);
        self::assertSame('xyz789', $updated->value);
        self::assertSame('session', $updated->name);
        self::assertSame('abc123', $cookie->value);
    }

    public function testWithExpires(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);
        $updated = $cookie->withExpires(9999);

        self::assertNotSame($cookie, $updated);
        self::assertSame(9999, $updated->expires);
        self::assertSame('session', $updated->name);
        self::assertSame(0, $cookie->expires);
    }

    public function testWithPath(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);
        $updated = $cookie->withPath('/admin');

        self::assertNotSame($cookie, $updated);
        self::assertSame('/admin', $updated->path);
        self::assertSame('session', $updated->name);
        self::assertSame('/', $cookie->path);
    }

    public function testWithDomain(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);
        $updated = $cookie->withDomain('example.com');

        self::assertNotSame($cookie, $updated);
        self::assertSame('example.com', $updated->domain);
        self::assertSame('session', $updated->name);
        self::assertSame('', $cookie->domain);
    }

    public function testWithSecure(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);
        $updated = $cookie->withSecure(true);

        self::assertNotSame($cookie, $updated);
        self::assertTrue($updated->secure);
        self::assertSame('session', $updated->name);
        self::assertFalse($cookie->secure);
    }

    public function testWithHttpOnly(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);
        $updated = $cookie->withHttpOnly(false);

        self::assertNotSame($cookie, $updated);
        self::assertFalse($updated->httpOnly);
        self::assertSame('session', $updated->name);
        self::assertTrue($cookie->httpOnly);
    }

    public function testWithSameSite(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);
        $updated = $cookie->withSameSite(SameSite::STRICT);

        self::assertNotSame($cookie, $updated);
        self::assertSame(SameSite::STRICT, $updated->sameSite);
        self::assertSame('session', $updated->name);
        self::assertSame(SameSite::LAX, $cookie->sameSite);
    }

    public function testSameSiteNoneWithSecureIsAccepted(): void
    {
        $cookie = new Cookie(
            name: 'session',
            value: 'abc123',
            expires: 0,
            secure: true,
            sameSite: SameSite::NONE,
        );

        self::assertSame(SameSite::NONE, $cookie->sameSite);
    }

    public function testSameSiteNoneWithoutSecureThrows(): void
    {
        $this->expectException(CookieException::class);

        new Cookie(
            name: 'session',
            value: 'abc123',
            expires: 0,
            sameSite: SameSite::NONE,
        );
    }

    public function testWithSameSiteNoneOnInsecureCookieThrows(): void
    {
        $cookie = new Cookie('session', 'abc123', 0);

        $this->expectException(CookieException::class);

        $cookie->withSameSite(SameSite::NONE);
    }

    public function testWithSecureFalseOnSameSiteNoneCookieThrows(): void
    {
        $cookie = new Cookie(
            name: 'session',
            value: 'abc123',
            expires: 0,
            secure: true,
            sameSite: SameSite::NONE,
        );

        $this->expectException(CookieException::class);

        $cookie->withSecure(false);
    }

    private static function fromSetCookie(
        string $header,
        string $url = 'https://www.example.com/account/settings',
    ): ?Cookie {
        return Cookie::tryFromSetCookie(
            header: $header,
            url: Url::parse($url),
            now: Instant::fromUnixTimestamp(1_700_000_000),
        );
    }

    public function testTryFromSetCookieReadsAttributes(): void
    {
        $cookie = self::fromSetCookie('id=a3fWa; Path=/; Secure; HttpOnly; SameSite=Strict');

        self::assertNotNull($cookie);
        self::assertSame('id', $cookie->name);
        self::assertSame('a3fWa', $cookie->value);
        self::assertSame('/', $cookie->path);
        self::assertSame('', $cookie->domain);
        self::assertSame(0, $cookie->expires);
        self::assertTrue($cookie->secure);
        self::assertTrue($cookie->httpOnly);
        self::assertSame(SameSite::STRICT, $cookie->sameSite);
    }

    public function testTryFromSetCookieDefaults(): void
    {
        $cookie = self::fromSetCookie('id=a3fWa');

        self::assertNotNull($cookie);
        self::assertSame('/account', $cookie->path);
        self::assertSame('', $cookie->domain);
        self::assertSame(0, $cookie->expires);
        self::assertFalse($cookie->secure);
        self::assertFalse($cookie->httpOnly);
        self::assertSame(SameSite::LAX, $cookie->sameSite);
    }

    public function testTryFromSetCookieKeepsQuotedValues(): void
    {
        self::assertSame('"b c"', self::fromSetCookie('a="b c"')?->value);
    }

    public function testTryFromSetCookieMaxAge(): void
    {
        self::assertSame(1_700_003_600, self::fromSetCookie('a=b; Max-Age=3600')?->expires);
        self::assertSame(1, self::fromSetCookie('a=b; Max-Age=0')?->expires);
        self::assertSame(1, self::fromSetCookie('a=b; Max-Age=-5')?->expires);
        self::assertSame(0, self::fromSetCookie('a=b; Max-Age=soon')?->expires);
    }

    public function testTryFromSetCookieMaxAgeWinsOverExpires(): void
    {
        self::assertSame(1_700_000_060, self::fromSetCookie('a=b; Expires=Wed, 21 Oct 2015 07:28:00 GMT; Max-Age=60')?->expires);
    }

    public function testTryFromSetCookieExpires(): void
    {
        self::assertSame(1_445_412_480, self::fromSetCookie('a=b; Expires=Wed, 21 Oct 2015 07:28:00 GMT')?->expires);
        self::assertSame(1_445_412_480, self::fromSetCookie('a=b; Expires=Wed, 21-Oct-2015 07:28:00 GMT')?->expires);
        self::assertSame(0, self::fromSetCookie('a=b; Expires=tomorrow')?->expires);
    }

    public function testTryFromSetCookieDomain(): void
    {
        self::assertSame('example.com', self::fromSetCookie('a=b; Domain=.Example.com')?->domain);
        self::assertSame('www.example.com', self::fromSetCookie('a=b; Domain=www.example.com')?->domain);
        self::assertNull(self::fromSetCookie('a=b; Domain=other.com'));
        self::assertNull(self::fromSetCookie('a=b; Domain=ample.com'));
    }

    /**
     * @return \Generator<array{0: string, 1: string}>
     */
    public static function defaultPathDataProvider(): \Generator
    {
        yield [
            'https://www.example.com',
            '/',
        ];

        yield [
            'https://www.example.com/',
            '/',
        ];

        yield [
            'https://www.example.com/a',
            '/',
        ];

        yield [
            'https://www.example.com/a/b',
            '/a',
        ];
    }

    #[DataProvider('defaultPathDataProvider')]
    public function testTryFromSetCookieDefaultPath(
        string $url,
        string $expected,
    ): void {
        self::assertSame($expected, self::fromSetCookie('a=b; Path=relative', $url)?->path);
    }

    public function testTryFromSetCookieSameSite(): void
    {
        self::assertSame(SameSite::NONE, self::fromSetCookie('a=b; SameSite=none; Secure')?->sameSite);
        self::assertSame(SameSite::LAX, self::fromSetCookie('a=b; SameSite=whatever')?->sameSite);
        self::assertNull(self::fromSetCookie('a=b; SameSite=None'));
    }

    public function testTryFromSetCookieRejectsInvalidPairs(): void
    {
        self::assertNull(self::fromSetCookie('noequals'));
        self::assertNull(self::fromSetCookie('=value'));
        self::assertNull(self::fromSetCookie('bad name=value'));
    }
}
