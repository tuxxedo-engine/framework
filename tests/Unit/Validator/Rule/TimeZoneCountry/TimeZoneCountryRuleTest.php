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

namespace Unit\Validator\Rule\TimeZoneCountry;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\TimeZoneCountry\TimeZoneCountryRule;
use Tuxxedo\Validator\Rule\TimeZoneCountry\TimeZoneCountryViolationCode;
use Tuxxedo\Validator\Rule\TimeZoneCountry\TimeZoneCountryViolationContext;
use Tuxxedo\Validator\ValidatorException;
use Tuxxedo\Validator\ViolationCodeInterface;

class TimeZoneCountryRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<string, array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'zone in the country' => [
            'Atlantic/Azores',
            null,
        ];

        yield 'time zone object in the country' => [
            TimeZone::parse('Europe/Lisbon'),
            null,
        ];

        yield 'zone in another country' => [
            'Europe/Madrid',
            TimeZoneCountryViolationCode::NOT_IN_COUNTRY,
        ];

        yield 'offset' => [
            '+00:00',
            TimeZoneCountryViolationCode::NOT_IN_COUNTRY,
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
            rule: new TimeZoneCountryRule(
                country: 'pt',
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

    public function testViolationCarriesTheNormalizedCountry(): void
    {
        $result = $this->runRule(
            rule: new TimeZoneCountryRule(
                country: 'pt',
            ),
            value: 'Europe/Madrid',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(TimeZoneCountryViolationContext::class, $result->context);
        self::assertSame('PT', $result->context->country);
    }

    public function testMalformedCountryIsRejected(): void
    {
        try {
            new TimeZoneCountryRule(
                country: 'Portugal',
            );

            self::fail('Expected ValidatorException');
        } catch (ValidatorException $exception) {
            self::assertStringContainsString(
                'two-letter ISO 3166-1 code',
                $exception->getMessage(),
            );
        }
    }
}
