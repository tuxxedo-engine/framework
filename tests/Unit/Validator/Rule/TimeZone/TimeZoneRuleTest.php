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

namespace Unit\Validator\Rule\TimeZone;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\TimeZone\TimeZoneRule;
use Tuxxedo\Validator\Rule\TimeZone\TimeZoneViolationCode;
use Tuxxedo\Validator\Rule\TimeZone\TimeZoneViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class TimeZoneRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'null skipped' => [
            null,
            null,
        ];

        yield 'iana name' => [
            'Europe/Copenhagen',
            null,
        ];

        yield 'utc' => [
            'UTC',
            null,
        ];

        yield 'unknown zone' => [
            'Middle/Earth',
            TimeZoneViolationCode::UNKNOWN_TIMEZONE,
        ];

        yield 'wrong type int' => [
            42,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'timezone instance accepted' => [
            TimeZone::utc(),
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new TimeZoneRule(),
            value: $value,
        );

        if ($expected === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expected, $result->code);
    }

    public function testUnknownZoneCarriesInputInContext(): void
    {
        $result = $this->runRule(
            rule: new TimeZoneRule(),
            value: 'Middle/Earth',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(TimeZoneViolationContext::class, $result->context);
        self::assertSame('Middle/Earth', $result->context->received);
    }
}
