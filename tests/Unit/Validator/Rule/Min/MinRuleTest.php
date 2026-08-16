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

namespace Unit\Validator\Rule\Min;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Min\MinRule;
use Tuxxedo\Validator\Rule\Min\MinViolationCode;
use Tuxxedo\Validator\Rule\Min\MinViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class MinRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: int|float, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'above min' => [
            10,
            5,
            null,
        ];

        yield 'at min' => [
            5,
            5,
            null,
        ];

        yield 'below min' => [
            2,
            5,
            MinViolationCode::BELOW_MIN,
        ];

        yield 'float above min' => [
            3.14,
            3.0,
            null,
        ];

        yield 'wrong type' => [
            'abc',
            5,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        int|float $min,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new MinRule(
                min: $min,
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

    public function testBelowMinCarriesContext(): void
    {
        $result = $this->runRule(
            rule: new MinRule(
                min: 5,
            ),
            value: 2,
        );

        self::assertNotNull($result);
        self::assertInstanceOf(MinViolationContext::class, $result->context);
        self::assertSame(2, $result->context->actual);
        self::assertSame(5, $result->context->min);
    }
}
