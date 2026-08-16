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

namespace Unit\Validator\Rule\CountryCode;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\CountryCode\CountryCodeRule;
use Tuxxedo\Validator\Rule\CountryCode\CountryCodeViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class CountryCodeRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'DK uppercase' => [
            'DK',
            null,
        ];

        yield 'us lowercase' => [
            'us',
            null,
        ];

        yield 'made-up code' => [
            'ZZ',
            CountryCodeViolationCode::NOT_RECOGNIZED,
        ];

        yield 'wrong type' => [
            42,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new CountryCodeRule(),
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
