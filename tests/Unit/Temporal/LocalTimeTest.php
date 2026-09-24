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
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\Temporal\TemporalException;
use Tuxxedo\Temporal\TimeZone;

class LocalTimeTest extends TestCase
{
    public function testOfHoldsComponents(): void
    {
        $time = LocalTime::of(hour: 14, minute: 30, second: 45, nanosecond: 123_000_000);

        self::assertSame(14, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
        self::assertSame(123_000_000, $time->nanosecond);
    }

    public function testOfDefaultsToWholeHour(): void
    {
        $time = LocalTime::of(hour: 9);

        self::assertSame(9, $time->hour);
        self::assertSame(0, $time->minute);
        self::assertSame(0, $time->second);
        self::assertSame(0, $time->nanosecond);
    }

    public function testMidnightFactory(): void
    {
        $time = LocalTime::midnight();

        self::assertSame('00:00:00', $time->toIso8601());
    }

    public function testNoonFactory(): void
    {
        $time = LocalTime::noon();

        self::assertSame('12:00:00', $time->toIso8601());
    }

    /**
     * @return iterable<string, array{int, int, int, int}>
     */
    public static function invalidComponents(): iterable
    {
        yield 'hour -1' => [
            -1,
            0,
            0,
            0,
        ];

        yield 'hour 24' => [
            24,
            0,
            0,
            0,
        ];

        yield 'minute 60' => [
            0,
            60,
            0,
            0,
        ];

        yield 'second 60' => [
            0,
            0,
            60,
            0,
        ];

        yield 'nanosecond 1e9' => [
            0,
            0,
            0,
            1_000_000_000,
        ];

        yield 'nanosecond negative' => [
            0,
            0,
            0,
            -1,
        ];
    }

    #[DataProvider('invalidComponents')]
    public function testOfRejectsInvalidComponents(
        int $hour,
        int $minute,
        int $second,
        int $nanosecond,
    ): void {
        $this->expectException(TemporalException::class);

        LocalTime::of(
            hour: $hour,
            minute: $minute,
            second: $second,
            nanosecond: $nanosecond,
        );
    }

    public function testParseAcceptsIso8601WithoutFraction(): void
    {
        $time = LocalTime::parse(input: '09:30:15');

        self::assertSame(9, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(15, $time->second);
        self::assertSame(0, $time->nanosecond);
    }

    public function testParseAcceptsFractionalSeconds(): void
    {
        $time = LocalTime::parse(input: '09:30:15.5');

        self::assertSame(500_000_000, $time->nanosecond);
    }

    public function testParseAcceptsFullNanosecondPrecision(): void
    {
        $time = LocalTime::parse(input: '09:30:15.123456789');

        self::assertSame(123_456_789, $time->nanosecond);
    }

    public function testParseRejectsMalformedInput(): void
    {
        $this->expectException(TemporalException::class);

        LocalTime::parse(input: 'nope');
    }

    public function testFromInstantExtractsWallClockInZone(): void
    {
        $time = LocalTime::fromInstant(
            instant: Instant::parse(input: '2026-07-16T09:30:15.500000Z'),
            timeZone: TimeZone::utc(),
        );

        self::assertSame(9, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(15, $time->second);
        self::assertSame(500_000_000, $time->nanosecond);
    }

    public function testEqualsMatchesAllComponents(): void
    {
        $a = LocalTime::of(hour: 14, minute: 30, second: 45);
        $b = LocalTime::parse(input: '14:30:45');

        self::assertTrue($a->equals(other: $b));
    }

    public function testIsBeforeAndIsAfterByHour(): void
    {
        $a = LocalTime::of(hour: 8);
        $b = LocalTime::of(hour: 9);

        self::assertTrue($a->isBefore(other: $b));
        self::assertTrue($b->isAfter(other: $a));
    }

    public function testIsBeforeByMinute(): void
    {
        $a = LocalTime::of(hour: 9, minute: 15);
        $b = LocalTime::of(hour: 9, minute: 30);

        self::assertTrue($a->isBefore(other: $b));
    }

    public function testIsBeforeBySecond(): void
    {
        $a = LocalTime::of(hour: 9, minute: 15, second: 10);
        $b = LocalTime::of(hour: 9, minute: 15, second: 20);

        self::assertTrue($a->isBefore(other: $b));
    }

    public function testIsBeforeByNanosecond(): void
    {
        $a = LocalTime::of(hour: 9, minute: 15, second: 10, nanosecond: 100);
        $b = LocalTime::of(hour: 9, minute: 15, second: 10, nanosecond: 200);

        self::assertTrue($a->isBefore(other: $b));
    }

    public function testCompareReturnsFalseForEqualTimes(): void
    {
        $time = LocalTime::of(hour: 9);

        self::assertFalse($time->isBefore(other: $time));
        self::assertFalse($time->isAfter(other: $time));
    }

    public function testOnDateProducesInstantInZone(): void
    {
        $instant = LocalTime::of(hour: 9, minute: 30)
            ->onDate(
                date: LocalDate::of(year: 2026, month: 7, day: 16),
                timeZone: TimeZone::utc(),
            );

        self::assertSame('2026-07-16T09:30:00+00:00', $instant->toIso8601());
    }

    public function testOnDatePreservesMicrosecondsFromNanosecond(): void
    {
        $instant = LocalTime::of(hour: 9, minute: 30, second: 15, nanosecond: 500_000_000)
            ->onDate(
                date: LocalDate::of(year: 2026, month: 7, day: 16),
                timeZone: TimeZone::utc(),
            );

        self::assertSame('500000', $instant->toDateTime()->format(format: 'u'));
    }

    public function testFormatDelegatesToPhpGrammar(): void
    {
        self::assertSame(
            '09:30 AM',
            LocalTime::of(hour: 9, minute: 30)->format(pattern: 'h:i A'),
        );
    }

    public function testToIso8601OmitsFractionWhenZero(): void
    {
        self::assertSame(
            '09:30:15',
            LocalTime::of(hour: 9, minute: 30, second: 15)->toIso8601(),
        );
    }

    public function testToIso8601IncludesFullNanosecondFractionWhenPresent(): void
    {
        self::assertSame(
            '09:30:15.000000500',
            LocalTime::of(hour: 9, minute: 30, second: 15, nanosecond: 500)->toIso8601(),
        );
    }
}
