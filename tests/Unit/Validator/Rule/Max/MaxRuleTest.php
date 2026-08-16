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

namespace Unit\Validator\Rule\Max;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Max\MaxRule;
use Tuxxedo\Validator\Rule\Max\MaxViolationCode;
use Tuxxedo\Validator\Rule\Max\MaxViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class MaxRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: int|float, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'below max' => [
            5,
            10,
            null,
        ];

        yield 'at max' => [
            10,
            10,
            null,
        ];

        yield 'above max' => [
            12,
            10,
            MaxViolationCode::ABOVE_MAX,
        ];

        yield 'float below max' => [
            3.14,
            5.0,
            null,
        ];

        yield 'wrong type' => [
            'abc',
            10,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        int|float $max,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new MaxRule(
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

    public function testAboveMaxCarriesContext(): void
    {
        $result = $this->runRule(
            rule: new MaxRule(
                max: 10,
            ),
            value: 15,
        );

        self::assertNotNull($result);
        self::assertInstanceOf(MaxViolationContext::class, $result->context);
        self::assertSame(15, $result->context->actual);
        self::assertSame(10, $result->context->max);
    }
}
