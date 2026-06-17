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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Cookie;

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
        );

        self::assertSame('/admin', $cookie->path);
        self::assertSame('example.com', $cookie->domain);
        self::assertTrue($cookie->secure);
        self::assertFalse($cookie->httpOnly);
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
}
