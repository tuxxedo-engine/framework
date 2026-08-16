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

namespace Unit\Validator\Rule\Url;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Url\UrlRule;
use Tuxxedo\Validator\Rule\Url\UrlViolationCode;
use Tuxxedo\Validator\Rule\Url\UrlViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class UrlRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null, 2: list<string>}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid https' => [
            'https://example.com',
            null,
            [
                'http',
                'https',
            ],
        ];

        yield 'valid http' => [
            'http://example.com',
            null,
            [
                'http',
                'https',
            ],
        ];

        yield 'invalid format' => [
            'not a url',
            UrlViolationCode::INVALID_FORMAT,
            [
                'http',
                'https',
            ],
        ];

        yield 'disallowed scheme' => [
            'ftp://example.com',
            UrlViolationCode::DISALLOWED_SCHEME,
            [
                'http',
                'https',
            ],
        ];

        yield 'wrong type' => [
            123,
            CommonViolationCode::WRONG_TYPE,
            [
                'http',
                'https',
            ],
        ];

        yield 'restricted scheme accepted' => [
            'wss://example.com/socket',
            null,
            [
                'wss',
            ],
        ];
    }

    /**
     * @param list<string> $allowedSchemes
     */
    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ViolationCodeInterface|null $expected,
        array $allowedSchemes,
    ): void {
        $result = $this->runRule(
            rule: new UrlRule(
                allowedSchemes: $allowedSchemes,
            ),
            value: $value,
        );

        if ($expected === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expected, $result->code);
    }

    public function testDisallowedSchemeCarriesSchemeAndAllowedListInContext(): void
    {
        $result = $this->runRule(
            rule: new UrlRule(
                allowedSchemes: [
                    'http',
                    'https',
                ],
            ),
            value: 'ftp://example.com',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(UrlViolationContext::class, $result->context);
        self::assertSame('ftp', $result->context->scheme);
        self::assertSame(
            [
                'http',
                'https',
            ],
            $result->context->allowed,
        );
    }
}
