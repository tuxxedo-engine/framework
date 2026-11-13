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

namespace Unit\Http\Client\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Http\Client\AuthClient;
use Support\Http\Client\ScriptedTransport;
use Tuxxedo\Http\Client\Auth\QueryAuth;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Method;

class QueryAuthTest extends TestCase
{
    /**
     * @return \Generator<array{0: string, 1: string}>
     */
    public static function queryDataProvider(): \Generator
    {
        yield [
            'https://api.example/x',
            'https://api.example/x?api%20key=s%26t',
        ];

        yield [
            'https://api.example/x?a=1&api%20key=old&b=2',
            'https://api.example/x?a=1&b=2&api%20key=s%26t',
        ];

        yield [
            'https://api.example/x?api%20key&a=1',
            'https://api.example/x?a=1&api%20key=s%26t',
        ];
    }

    #[DataProvider('queryDataProvider')]
    public function testReplacesTheParameter(
        string $url,
        string $expected,
    ): void {
        $request = new QueryAuth('api key', 's&t')->apply(
            new ClientRequest(Method::GET, $url),
            AuthClient::create(new ScriptedTransport()),
        );

        self::assertSame($expected, (string) $request->url);
    }
}
