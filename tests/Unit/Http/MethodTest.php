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
use Tuxxedo\Http\Method;

class MethodTest extends TestCase
{
    /**
     * @return \Generator<array{0: string, 1: Method}>
     */
    public static function fromDataProvider(): \Generator
    {
        yield [
            'GET',
            Method::GET,
        ];

        yield [
            'HEAD',
            Method::HEAD,
        ];

        yield [
            'POST',
            Method::POST,
        ];

        yield [
            'PUT',
            Method::PUT,
        ];

        yield [
            'DELETE',
            Method::DELETE,
        ];

        yield [
            'CONNECT',
            Method::CONNECT,
        ];

        yield [
            'OPTIONS',
            Method::OPTIONS,
        ];

        yield [
            'TRACE',
            Method::TRACE,
        ];

        yield [
            'PATCH',
            Method::PATCH,
        ];
    }

    #[DataProvider('fromDataProvider')]
    public function testFromExactMatch(
        string $name,
        Method $expected,
    ): void {
        self::assertSame($expected, Method::from($name));
    }

    public function testFromCaseInsensitive(): void
    {
        self::assertSame(Method::POST, Method::from('post'));
        self::assertSame(Method::POST, Method::from('Post'));
        self::assertSame(Method::POST, Method::from('pOsT'));
    }

    public function testFromUnknownFallsBackToGet(): void
    {
        self::assertSame(Method::GET, Method::from('unknown'));
    }
}
