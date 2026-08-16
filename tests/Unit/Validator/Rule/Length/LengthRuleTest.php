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

namespace Unit\Validator\Rule\Length;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Length\LengthRule;
use Tuxxedo\Validator\Rule\Length\LengthViolationCode;
use Tuxxedo\Validator\Rule\Length\LengthViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class LengthRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: ?int, 2: ?int, 3: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'within bounds' => [
            'hello',
            3,
            10,
            null,
        ];

        yield 'at min boundary' => [
            'abc',
            3,
            10,
            null,
        ];

        yield 'at max boundary' => [
            'abcdefghij',
            3,
            10,
            null,
        ];

        yield 'too short' => [
            'hi',
            3,
            10,
            LengthViolationCode::TOO_SHORT,
        ];

        yield 'too long' => [
            'this is much longer than allowed',
            3,
            10,
            LengthViolationCode::TOO_LONG,
        ];

        yield 'only min defined, matches' => [
            'longer string',
            5,
            null,
            null,
        ];

        yield 'only max defined, matches' => [
            'x',
            null,
            5,
            null,
        ];

        yield 'wrong type' => [
            42,
            0,
            100,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?int $min,
        ?int $max,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new LengthRule(
                min: $min,
                max: $max,
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

    public function testTooShortCarriesMinAndActualInContext(): void
    {
        $result = $this->runRule(
            rule: new LengthRule(
                min: 5,
            ),
            value: 'ab',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(LengthViolationContext::class, $result->context);
        self::assertSame(5, $result->context->min);
        self::assertSame(2, $result->context->actual);
    }

    public function testTooLongCarriesMaxAndActualInContext(): void
    {
        $result = $this->runRule(
            rule: new LengthRule(
                max: 3,
            ),
            value: 'abcdef',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(LengthViolationContext::class, $result->context);
        self::assertSame(3, $result->context->max);
        self::assertSame(6, $result->context->actual);
    }
}
