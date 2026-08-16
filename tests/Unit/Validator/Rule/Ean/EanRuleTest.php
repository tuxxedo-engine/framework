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

namespace Unit\Validator\Rule\Ean;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Ean\EanRule;
use Tuxxedo\Validator\Rule\Ean\EanViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class EanRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid ean-13' => [
            '4006381333931',
            null,
        ];

        yield 'valid ean-8' => [
            '73513537',
            null,
        ];

        yield 'wrong length' => [
            '12345',
            EanViolationCode::INVALID_FORMAT,
        ];

        yield 'non-digit' => [
            '400638133393A',
            EanViolationCode::INVALID_FORMAT,
        ];

        yield 'invalid checksum' => [
            '4006381333930',
            EanViolationCode::INVALID_CHECKSUM,
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
            rule: new EanRule(),
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
