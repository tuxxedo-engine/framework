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

namespace Unit\Validator\Rule\AlphaNumeric;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\AlphaNumeric\AlphaNumericRule;
use Tuxxedo\Validator\Rule\AlphaNumeric\AlphaNumericViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class AlphaNumericRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'letters and digits' => [
            'abc123',
            null,
        ];

        yield 'letters only' => [
            'abc',
            null,
        ];

        yield 'digits only' => [
            '123',
            null,
        ];

        yield 'special chars fails' => [
            'abc-123',
            AlphaNumericViolationCode::NOT_ALPHANUMERIC,
        ];

        yield 'empty fails' => [
            '',
            AlphaNumericViolationCode::NOT_ALPHANUMERIC,
        ];

        yield 'wrong type' => [
            42,
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
            rule: new AlphaNumericRule(),
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
