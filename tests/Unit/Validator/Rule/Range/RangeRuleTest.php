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

namespace Unit\Validator\Rule\Range;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Range\RangeRule;
use Tuxxedo\Validator\Rule\Range\RangeViolationCode;
use Tuxxedo\Validator\Rule\Range\RangeViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class RangeRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: int|float, 2: int|float, 3: bool, 4: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'inside inclusive range' => [
            5,
            1,
            10,
            true,
            null,
        ];

        yield 'at min inclusive' => [
            1,
            1,
            10,
            true,
            null,
        ];

        yield 'at max inclusive' => [
            10,
            1,
            10,
            true,
            null,
        ];

        yield 'below min inclusive' => [
            0,
            1,
            10,
            true,
            RangeViolationCode::BELOW_MIN,
        ];

        yield 'above max inclusive' => [
            11,
            1,
            10,
            true,
            RangeViolationCode::ABOVE_MAX,
        ];

        yield 'at min exclusive fails' => [
            1,
            1,
            10,
            false,
            RangeViolationCode::BELOW_MIN,
        ];

        yield 'at max exclusive fails' => [
            10,
            1,
            10,
            false,
            RangeViolationCode::ABOVE_MAX,
        ];

        yield 'numeric string in range' => [
            '5',
            1,
            10,
            true,
            null,
        ];

        yield 'float in range' => [
            3.14,
            1.0,
            10.0,
            true,
            null,
        ];

        yield 'non-numeric wrong type' => [
            'nope',
            1,
            10,
            true,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            1,
            10,
            true,
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        int|float $min,
        int|float $max,
        bool $inclusive,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new RangeRule(
                min: $min,
                max: $max,
                inclusive: $inclusive,
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

    public function testBelowMinCarriesActualAndBoundsInContext(): void
    {
        $result = $this->runRule(
            rule: new RangeRule(
                min: 5,
                max: 10,
            ),
            value: 2,
        );

        self::assertNotNull($result);
        self::assertInstanceOf(RangeViolationContext::class, $result->context);
        self::assertSame(2, $result->context->actual);
        self::assertSame(5, $result->context->min);
        self::assertSame(10, $result->context->max);
        self::assertTrue($result->context->inclusive);
    }
}
