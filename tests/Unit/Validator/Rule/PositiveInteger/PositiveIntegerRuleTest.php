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

namespace Unit\Validator\Rule\PositiveInteger;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\PositiveInteger\PositiveIntegerRule;
use Tuxxedo\Validator\Rule\PositiveInteger\PositiveIntegerViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class PositiveIntegerRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'positive int' => [
            5,
            null,
        ];

        yield 'zero' => [
            0,
            PositiveIntegerViolationCode::NOT_POSITIVE,
        ];

        yield 'negative int' => [
            -1,
            PositiveIntegerViolationCode::NOT_POSITIVE,
        ];

        yield 'float fails wrong type' => [
            3.14,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'string fails wrong type' => [
            '5',
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new PositiveIntegerRule(),
            value: $value,
        );

        if ($expected === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expected, $result->code);
    }
}
