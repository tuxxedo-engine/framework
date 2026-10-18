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

namespace Unit\Validator\Rule\Locale;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Locale\LocaleRule;
use Tuxxedo\Validator\Rule\Locale\LocaleViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class LocaleRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<string, array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'language and region' => [
            'cs-CZ',
            null,
        ];

        yield 'underscore and lower case' => [
            'en_us',
            null,
        ];

        yield 'language only' => [
            'da',
            null,
        ];

        yield 'locale object' => [
            Locale::parse('pt-BR'),
            null,
        ];

        yield 'word' => [
            'english',
            LocaleViolationCode::INVALID,
        ];

        yield 'garbage' => [
            'not a tag',
            LocaleViolationCode::INVALID,
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
        ViolationCodeInterface|null $expectedCode,
    ): void {
        $result = $this->runRule(
            rule: new LocaleRule(),
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
