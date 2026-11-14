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

namespace Unit\Http\Client\Rules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Client\Rules\Rule;
use Tuxxedo\Http\Client\Rules\RuleAction;
use Tuxxedo\Http\Method;

class RuleTest extends TestCase
{
    /**
     * @return \Generator<array{0: string, 1: Method, 2: string, 3: bool}>
     */
    public static function matchDataProvider(): \Generator
    {
        yield [
            'api.example',
            Method::GET,
            'https://api.example/x',
            true,
        ];

        yield [
            'api.example',
            Method::GET,
            'https://API.example/x',
            true,
        ];

        yield [
            'api.example',
            Method::GET,
            'https://other.example/x',
            false,
        ];

        yield [
            '*.example',
            Method::GET,
            'https://a.b.example/',
            true,
        ];

        yield [
            '*.example',
            Method::GET,
            'https://example/',
            false,
        ];

        yield [
            'api.example/v1/*',
            Method::GET,
            'https://api.example/v1/users/1',
            true,
        ];

        yield [
            'api.example/v1/*',
            Method::GET,
            'https://api.example/v2/users',
            false,
        ];

        yield [
            'api.example/',
            Method::GET,
            'https://api.example',
            true,
        ];

        yield [
            'POST api.example',
            Method::POST,
            'https://api.example/',
            true,
        ];

        yield [
            'post api.example',
            Method::GET,
            'https://api.example/',
            false,
        ];

        yield [
            'http://api.example',
            Method::GET,
            'http://api.example/',
            true,
        ];

        yield [
            'http://api.example',
            Method::GET,
            'https://api.example/',
            false,
        ];

        yield [
            '10.0.0.0/8',
            Method::GET,
            'http://10.1.2.3/x',
            true,
        ];

        yield [
            '10.0.0.0/8',
            Method::GET,
            'http://11.1.2.3/x',
            false,
        ];

        yield [
            '10.0.0.0/8',
            Method::GET,
            'http://ten.example/x',
            false,
        ];

        yield [
            'fc00::/7',
            Method::GET,
            'http://[fd00::1]/',
            true,
        ];
    }

    #[DataProvider('matchDataProvider')]
    public function testMatches(
        string $pattern,
        Method $method,
        string $url,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            new Rule($pattern, RuleAction::DENY)->matches(new ClientRequest($method, $url)),
        );
    }

    public function testExposesItsParts(): void
    {
        $rule = new Rule('put https://api.example/x', RuleAction::ALLOW);

        self::assertSame('PUT', $rule->method);
        self::assertSame('https', $rule->scheme);
        self::assertNull($rule->network);
        self::assertNull($rule->handler);
        self::assertSame(RuleAction::ALLOW, $rule->action);
        self::assertSame('put https://api.example/x', $rule->pattern);
    }
}
