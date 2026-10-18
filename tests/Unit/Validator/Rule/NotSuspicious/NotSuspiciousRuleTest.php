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

namespace Unit\Validator\Rule\NotSuspicious;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\NotSuspicious\NotSuspiciousRule;
use Tuxxedo\Validator\Rule\NotSuspicious\NotSuspiciousViolationCode;
use Tuxxedo\Validator\Rule\NotSuspicious\SpoofLevel;
use Tuxxedo\Validator\ViolationCodeInterface;

class NotSuspiciousRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<string, array{0: mixed, 1: SpoofLevel, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'plain latin' => [
            'paypal',
            SpoofLevel::HIGHLY_RESTRICTIVE,
            null,
        ];

        yield 'latin with diacritics' => [
            'Ærø',
            SpoofLevel::HIGHLY_RESTRICTIVE,
            null,
        ];

        yield 'single non-latin script' => [
            'Ελλάδα',
            SpoofLevel::HIGHLY_RESTRICTIVE,
            null,
        ];

        yield 'cyrillic a mixed into latin' => [
            "\u{0430}dmin",
            SpoofLevel::HIGHLY_RESTRICTIVE,
            NotSuspiciousViolationCode::SUSPICIOUS,
        ];

        yield 'ascii level rejects diacritics' => [
            'Ærø',
            SpoofLevel::ASCII,
            NotSuspiciousViolationCode::SUSPICIOUS,
        ];

        yield 'ascii level accepts ascii' => [
            'admin',
            SpoofLevel::ASCII,
            null,
        ];

        yield 'single script restrictive rejects mixed scripts' => [
            "\u{0430}dmin",
            SpoofLevel::SINGLE_SCRIPT_RESTRICTIVE,
            NotSuspiciousViolationCode::SUSPICIOUS,
        ];

        yield 'moderately restrictive' => [
            'paypal',
            SpoofLevel::MODERATELY_RESTRICTIVE,
            null,
        ];

        yield 'minimally restrictive' => [
            'paypal',
            SpoofLevel::MINIMALLY_RESTRICTIVE,
            null,
        ];

        yield 'unrestrictive' => [
            'paypal',
            SpoofLevel::UNRESTRICTIVE,
            null,
        ];

        yield 'wrong type' => [
            42,
            SpoofLevel::HIGHLY_RESTRICTIVE,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            SpoofLevel::HIGHLY_RESTRICTIVE,
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        SpoofLevel $level,
        ViolationCodeInterface|null $expectedCode,
    ): void {
        $result = $this->runRule(
            rule: new NotSuspiciousRule(
                level: $level,
            ),
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
