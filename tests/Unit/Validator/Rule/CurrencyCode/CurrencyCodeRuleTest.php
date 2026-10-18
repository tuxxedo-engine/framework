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

namespace Unit\Validator\Rule\CurrencyCode;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\CurrencyCode\CurrencyCodeRule;
use Tuxxedo\Validator\Rule\CurrencyCode\CurrencyCodeViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class CurrencyCodeRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<string, array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'euro' => [
            'EUR',
            null,
        ];

        yield 'lower case' => [
            'dkk',
            null,
        ];

        yield 'replaced code still valid in its new form' => [
            'ZWG',
            null,
        ];

        yield 'too long' => [
            'EURO',
            CurrencyCodeViolationCode::NOT_RECOGNIZED,
        ];

        yield 'unknown' => [
            'XYZ',
            CurrencyCodeViolationCode::NOT_RECOGNIZED,
        ];

        yield 'withdrawn' => [
            'HRK',
            CurrencyCodeViolationCode::NOT_RECOGNIZED,
        ];

        yield 'wrong type' => [
            978,
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
        ViolationCodeInterface|null $expectedCode,
    ): void {
        $result = $this->runRule(
            rule: new CurrencyCodeRule(),
            value: $value,
        );

        if ($expectedCode === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expectedCode, $result->code);
    }
}
