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

namespace Unit\Validator\Rule\NegativeInteger;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\NegativeInteger\NegativeIntegerRule;
use Tuxxedo\Validator\Rule\NegativeInteger\NegativeIntegerViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class NegativeIntegerRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'negative int' => [
            -5,
            null,
        ];

        yield 'zero' => [
            0,
            NegativeIntegerViolationCode::NOT_NEGATIVE,
        ];

        yield 'positive int' => [
            1,
            NegativeIntegerViolationCode::NOT_NEGATIVE,
        ];

        yield 'float fails wrong type' => [
            -3.14,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'string fails wrong type' => [
            '-5',
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new NegativeIntegerRule(),
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
