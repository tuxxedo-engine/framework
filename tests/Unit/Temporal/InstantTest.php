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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Temporal\DayOfWeek;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\Month;
use Tuxxedo\Temporal\TemporalException;
use Tuxxedo\Temporal\TimeZone;

class InstantTest extends TestCase
{
    public function testNowUsesGivenClock(): void
    {
        $fixed = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );
        $clock = new FixedClock(
            instant: $fixed,
        );

        self::assertSame($fixed, Instant::now(clock: $clock));
    }

    public function testNowFallsBackToSystemClockWhenOmitted(): void
    {
        $before = \time();
        $now = Instant::now();
        $after = \time();

        self::assertGreaterThanOrEqual($before, $now->toUnixTimestamp());
        self::assertLessThanOrEqual($after, $now->toUnixTimestamp());
    }

    public function testParseAcceptsIso8601(): void
    {
        $instant = Instant::parse(
            input: '2026-01-01T12:34:56Z',
        );

        self::assertSame('2026-01-01T12:34:56+00:00', $instant->toIso8601());
    }

    public function testParseAppliesDefaultTimezone(): void
    {
        $instant = Instant::parse(
            input: '2026-01-01 12:00:00',
            default: TimeZone::parse(input: 'Europe/Copenhagen'),
        );

        self::assertSame(
            'Europe/Copenhagen',
            $instant->toDateTime()->getTimezone()->getName(),
        );
    }

    public function testParseRejectsMalformedInput(): void
    {
        $this->expectException(TemporalException::class);

        Instant::parse(
            input: 'not a date',
        );
    }

    public function testFromUnixTimestampBuildsInstant(): void
    {
        $instant = Instant::fromUnixTimestamp(
            timestamp: 1_756_800_000,
        );

        self::assertSame(1_756_800_000, $instant->toUnixTimestamp());
    }

    public function testFromUnixTimestampAppliesTimezone(): void
    {
        $instant = Instant::fromUnixTimestamp(
            timestamp: 0,
            timeZone: TimeZone::parse(input: 'Europe/Copenhagen'),
        );

        self::assertSame(
            'Europe/Copenhagen',
            $instant->toDateTime()->getTimezone()->getName(),
        );
    }

    public function testFromDateTimeWrapsExisting(): void
    {
        $dateTime = new \DateTimeImmutable(
            datetime: '2026-06-15T09:00:00Z',
        );

        $instant = Instant::fromDateTime(
            dateTime: $dateTime,
        );

        self::assertSame($dateTime, $instant->toDateTime());
        self::assertSame(0, $instant->nanosecondFraction);
    }

    public function testPlusAddsDuration(): void
    {
        $start = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );

        $result = $start->plus(
            duration: Duration::fromSeconds(seconds: 90),
        );

        self::assertSame($start->toUnixTimestamp() + 90, $result->toUnixTimestamp());
    }

    public function testMinusSubtractsDuration(): void
    {
        $start = Instant::parse(
            input: '2026-01-01T00:01:30Z',
        );

        $result = $start->minus(
            duration: Duration::fromSeconds(seconds: 60),
        );

        self::assertSame($start->toUnixTimestamp() - 60, $result->toUnixTimestamp());
    }

    public function testPlusNegativeDurationGoesBackward(): void
    {
        $start = Instant::parse(
            input: '2026-01-01T00:01:00Z',
        );

        $result = $start->plus(
            duration: Duration::fromSeconds(seconds: 30)->negate(),
        );

        self::assertSame($start->toUnixTimestamp() - 30, $result->toUnixTimestamp());
    }

    public function testPlusCarriesSubsecondPrecision(): void
    {
        $start = Instant::fromUnixTimestamp(
            timestamp: 100,
        );

        $result = $start->plus(
            duration: Duration::fromSeconds(seconds: 0, nanoseconds: 250_000_000),
        );

        self::assertSame(100, $result->toUnixTimestamp());
        self::assertSame('250000', $result->toDateTime()->format(format: 'u'));
    }

    public function testDifferencePositive(): void
    {
        $earlier = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );

        $later = Instant::parse(
            input: '2026-01-01T00:01:00Z',
        );

        $diff = $later->difference(
            other: $earlier,
        );

        self::assertSame(60, $diff->seconds);
        self::assertFalse($diff->negative);
    }

    public function testDifferenceNegative(): void
    {
        $earlier = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );

        $later = Instant::parse(
            input: '2026-01-01T00:01:00Z',
        );

        $diff = $earlier->difference(
            other: $later,
        );

        self::assertSame(60, $diff->seconds);
        self::assertTrue($diff->negative);
    }

    public function testDifferenceOfSameInstantIsZero(): void
    {
        $instant = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );

        self::assertTrue(
            $instant->difference(other: $instant)->isZero(),
        );
    }

    public function testIsBeforeAndIsAfter(): void
    {
        $earlier = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );

        $later = Instant::parse(
            input: '2026-01-01T00:01:00Z',
        );

        self::assertTrue($earlier->isBefore(other: $later));
        self::assertTrue($later->isAfter(other: $earlier));
        self::assertFalse($earlier->isAfter(other: $later));
        self::assertFalse($later->isBefore(other: $earlier));
    }

    public function testEqualsRequiresIdenticalMoment(): void
    {
        $a = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );

        $b = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );

        self::assertTrue($a->equals(other: $b));
    }

    public function testEqualsFalseForDifferentMoment(): void
    {
        $a = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );

        $b = Instant::parse(
            input: '2026-01-01T00:00:01Z',
        );

        self::assertFalse($a->equals(other: $b));
    }

    public function testFormatUsesPhpGrammar(): void
    {
        $instant = Instant::parse(
            input: '2026-01-01T12:34:56Z',
        );

        self::assertSame(
            '2026-01-01 12:34:56',
            $instant->format(pattern: 'Y-m-d H:i:s'),
        );
    }

    public function testToIso8601AndToAtomAreEqual(): void
    {
        $instant = Instant::parse(
            input: '2026-01-01T12:34:56Z',
        );

        self::assertSame($instant->toIso8601(), $instant->toAtom());
    }

    public function testToRfc3339Format(): void
    {
        $instant = Instant::parse(
            input: '2026-01-01T12:34:56Z',
        );

        self::assertSame('2026-01-01T12:34:56+00:00', $instant->toRfc3339());
    }

    public function testMinusAcrossEpochCarriesNanoseconds(): void
    {
        $start = Instant::fromUnixTimestamp(
            timestamp: 0,
        );

        $result = $start->minus(
            duration: Duration::fromNanoseconds(nanoseconds: 5),
        );

        self::assertSame(-1, $result->toUnixTimestamp());
        self::assertSame('999999', $result->toDateTime()->format(format: 'u'));
        self::assertSame(995, $result->nanosecondFraction);
    }

    public function testToDateTimeReturnsWrappedValue(): void
    {
        $dateTime = new \DateTimeImmutable(
            datetime: '2026-01-01T00:00:00Z',
        );

        $instant = Instant::fromDateTime(
            dateTime: $dateTime,
        );

        self::assertSame($dateTime, $instant->toDateTime());
    }

    public function testDayOfWeekReturnsIsoWeekday(): void
    {
        $instant = Instant::parse(
            input: '2026-01-05T00:00:00Z',
        );

        self::assertSame(DayOfWeek::MONDAY, $instant->dayOfWeek());
    }

    public function testMonthReturnsCalendarMonth(): void
    {
        $instant = Instant::parse(
            input: '2026-07-15T00:00:00Z',
        );

        self::assertSame(Month::JULY, $instant->month());
    }

    public function testWithTimeZoneShiftsPresentationZone(): void
    {
        $instant = Instant::parse(
            input: '2026-07-16T12:00:00Z',
        );

        $shifted = $instant->withTimeZone(
            timeZone: TimeZone::parse(input: 'America/New_York'),
        );

        self::assertSame(
            'America/New_York',
            $shifted->toDateTime()->getTimezone()->getName(),
        );
        self::assertSame($instant->toUnixTimestamp(), $shifted->toUnixTimestamp());
    }

    public function testWithTimeZonePreservesNanosecondFraction(): void
    {
        $instant = Instant::fromUnixTimestamp(timestamp: 0)
            ->minus(duration: Duration::fromNanoseconds(nanoseconds: 5));

        $shifted = $instant->withTimeZone(timeZone: TimeZone::utc());

        self::assertSame($instant->nanosecondFraction, $shifted->nanosecondFraction);
    }
}
