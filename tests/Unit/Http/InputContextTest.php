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
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Method;

class InputContextTest extends TestCase
{
    /**
     * @return \Generator<array{0: Method, 1: InputContext}>
     */
    public static function fromMethodDataProvider(): \Generator
    {
        yield [
            Method::GET,
            InputContext::GET,
        ];

        yield [
            Method::POST,
            InputContext::POST,
        ];
    }

    #[DataProvider('fromMethodDataProvider')]
    public function testFromMethod(
        Method $method,
        InputContext $expected,
    ): void {
        self::assertSame($expected, InputContext::fromMethod($method));
    }

    /**
     * @return \Generator<array{0: Method}>
     */
    public static function fromMethodThrowsDataProvider(): \Generator
    {
        yield [
            Method::HEAD,
        ];

        yield [
            Method::PUT,
        ];

        yield [
            Method::DELETE,
        ];

        yield [
            Method::PATCH,
        ];
    }

    #[DataProvider('fromMethodThrowsDataProvider')]
    public function testFromMethodThrowsForUnsupportedMethod(
        Method $method,
    ): void {
        $this->expectException(HttpException::class);

        InputContext::fromMethod($method);
    }
}
