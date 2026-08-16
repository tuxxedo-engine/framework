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

namespace Unit\Validator\Rule\Numeric;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Numeric\NumericRule;
use Tuxxedo\Validator\ViolationCodeInterface;

class NumericRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: bool, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'int passes lenient' => [
            42,
            false,
            null,
        ];

        yield 'float passes lenient' => [
            3.14,
            false,
            null,
        ];

        yield 'numeric string passes lenient' => [
            '42',
            false,
            null,
        ];

        yield 'non-numeric string fails lenient' => [
            'abc',
            false,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'int passes strict' => [
            42,
            true,
            null,
        ];

        yield 'float passes strict' => [
            3.14,
            true,
            null,
        ];

        yield 'numeric string fails strict' => [
            '42',
            true,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null fails' => [
            null,
            false,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        bool $strict,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new NumericRule(
                strict: $strict,
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
}
