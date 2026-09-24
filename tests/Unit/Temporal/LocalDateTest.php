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

namespace Unit\Temporal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\Month;
use Tuxxedo\Temporal\TemporalException;
use Tuxxedo\Temporal\TimeZone;

class LocalDateTest extends TestCase
{
    public function testOfHoldsComponents(): void
    {
        $date = LocalDate::of(year: 2026, month: 7, day: 16);

        self::assertSame(2026, $date->year);
        self::assertSame(Month::JULY, $date->month);
        self::assertSame(16, $date->day);
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function invalidComponents(): iterable
    {
        yield 'month 0' => [
            2026,
            0,
            15,
        ];

        yield 'month 13' => [
            2026,
            13,
            15,
        ];

        yield 'day 0' => [
            2026,
            5,
            0,
        ];

        yield 'day 32 in january' => [
            2026,
            1,
            32,
        ];

        yield 'february 29 in common year' => [
            2026,
            2,
            29,
        ];
    }

    #[DataProvider('invalidComponents')]
    public function testOfRejectsInvalidComponents(
        int $year,
        int $month,
        int $day,
    ): void {
        $this->expectException(TemporalException::class);

        LocalDate::of(year: $year, month: $month, day: $day);
    }

    public function testFebruary29AllowedOnLeapYear(): void
    {
        $date = LocalDate::of(year: 2028, month: 2, day: 29);

        self::assertSame(29, $date->day);
    }

    public function testParseAcceptsIsoDate(): void
    {
        $date = LocalDate::parse(input: '2026-07-16');

        self::assertSame('2026-07-16', $date->toIso8601());
    }

    public function testParseRejectsMalformedInput(): void
    {
        $this->expectException(TemporalException::class);

        LocalDate::parse(input: 'nope');
    }

    public function testFromInstantExtractsComponentsInGivenZone(): void
    {
        $date = LocalDate::fromInstant(
            instant: Instant::parse(input: '2026-07-16T23:30:00Z'),
            timeZone: TimeZone::parse(input: 'America/New_York'),
        );

        self::assertSame('2026-07-16', $date->toIso8601());
    }

    public function testEqualsMatchesAllComponents(): void
    {
        $a = LocalDate::of(year: 2026, month: 7, day: 16);
        $b = LocalDate::parse(input: '2026-07-16');

        self::assertTrue($a->equals(other: $b));
    }

    public function testIsBeforeAndIsAfterByYear(): void
    {
        $a = LocalDate::of(year: 2025, month: 12, day: 31);
        $b = LocalDate::of(year: 2026, month: 1, day: 1);

        self::assertTrue($a->isBefore(other: $b));
        self::assertTrue($b->isAfter(other: $a));
    }

    public function testIsBeforeByMonth(): void
    {
        $a = LocalDate::of(year: 2026, month: 3, day: 15);
        $b = LocalDate::of(year: 2026, month: 4, day: 1);

        self::assertTrue($a->isBefore(other: $b));
    }

    public function testIsBeforeByDay(): void
    {
        $a = LocalDate::of(year: 2026, month: 5, day: 10);
        $b = LocalDate::of(year: 2026, month: 5, day: 11);

        self::assertTrue($a->isBefore(other: $b));
    }

    public function testCompareReturnsFalseForEqualDates(): void
    {
        $date = LocalDate::of(year: 2026, month: 7, day: 16);

        self::assertFalse($date->isBefore(other: $date));
        self::assertFalse($date->isAfter(other: $date));
    }

    public function testAtStartOfDayAnchorsInGivenZone(): void
    {
        $instant = LocalDate::of(year: 2026, month: 7, day: 16)
            ->atStartOfDay(timeZone: TimeZone::parse(input: 'UTC'));

        self::assertSame('2026-07-16T00:00:00+00:00', $instant->toIso8601());
    }

    public function testFormatDelegatesToPhpGrammar(): void
    {
        self::assertSame(
            '16/07/2026',
            LocalDate::of(year: 2026, month: 7, day: 16)->format(pattern: 'd/m/Y'),
        );
    }

    public function testPlusDaysAndMinusDays(): void
    {
        $date = LocalDate::of(year: 2026, month: 7, day: 16);

        self::assertSame('2026-07-19', $date->plusDays(days: 3)->toIso8601());
        self::assertSame('2026-07-13', $date->minusDays(days: 3)->toIso8601());
    }

    public function testPlusMonthsAndMinusMonths(): void
    {
        $date = LocalDate::of(year: 2026, month: 7, day: 16);

        self::assertSame('2026-10-16', $date->plusMonths(months: 3)->toIso8601());
        self::assertSame('2026-04-16', $date->minusMonths(months: 3)->toIso8601());
    }

    public function testPlusYearsAndMinusYears(): void
    {
        $date = LocalDate::of(year: 2026, month: 7, day: 16);

        self::assertSame('2029-07-16', $date->plusYears(years: 3)->toIso8601());
        self::assertSame('2023-07-16', $date->minusYears(years: 3)->toIso8601());
    }

    public function testPlusMonthsOverflowsAcrossYear(): void
    {
        $date = LocalDate::of(year: 2026, month: 11, day: 30);

        self::assertSame('2026-12-30', $date->plusMonths(months: 1)->toIso8601());
        self::assertSame('2027-01-30', $date->plusMonths(months: 2)->toIso8601());
    }
}
