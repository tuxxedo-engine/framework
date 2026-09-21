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
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\Period;
use Tuxxedo\Temporal\TemporalException;

class PeriodTest extends TestCase
{
    private function instant(
        string $iso,
    ): Instant {
        return Instant::parse(
            input: $iso,
        );
    }

    public function testOfHoldsBothBoundaries(): void
    {
        $start = $this->instant(iso: '2026-01-01T00:00:00Z');
        $end = $this->instant(iso: '2026-01-02T00:00:00Z');

        $period = Period::of(
            start: $start,
            end: $end,
        );

        self::assertSame($start, $period->start);
        self::assertSame($end, $period->end);
    }

    public function testOfAllowsZeroLengthPeriod(): void
    {
        $moment = $this->instant(iso: '2026-01-01T00:00:00Z');

        $period = Period::of(
            start: $moment,
            end: $moment,
        );

        self::assertTrue($period->length()->isZero());
    }

    public function testOfRejectsInvertedBoundaries(): void
    {
        $caught = null;

        try {
            Period::of(
                start: $this->instant(iso: '2026-01-02T00:00:00Z'),
                end: $this->instant(iso: '2026-01-01T00:00:00Z'),
            );
        } catch (TemporalException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(TemporalException::class, $caught);
        self::assertStringContainsString('must not precede', $caught->getMessage());
    }

    public function testFromDurationExtendsForward(): void
    {
        $start = $this->instant(iso: '2026-01-01T00:00:00Z');

        $period = Period::fromDuration(
            start: $start,
            duration: Duration::fromMinutes(minutes: 30),
        );

        self::assertSame($start, $period->start);
        self::assertSame(30 * 60, $period->end->toUnixTimestamp() - $start->toUnixTimestamp());
    }

    public function testFromDurationRejectsNegativeDuration(): void
    {
        $caught = null;

        try {
            Period::fromDuration(
                start: $this->instant(iso: '2026-01-01T00:00:00Z'),
                duration: Duration::fromMinutes(minutes: 5)->negate(),
            );
        } catch (TemporalException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(TemporalException::class, $caught);
        self::assertStringContainsString('must not precede', $caught->getMessage());
    }

    public function testContainsIsInclusiveAtStart(): void
    {
        $start = $this->instant(iso: '2026-01-01T00:00:00Z');
        $end = $this->instant(iso: '2026-01-02T00:00:00Z');

        $period = Period::of(
            start: $start,
            end: $end,
        );

        self::assertTrue($period->contains(instant: $start));
    }

    public function testContainsIsInclusiveAtEnd(): void
    {
        $start = $this->instant(iso: '2026-01-01T00:00:00Z');
        $end = $this->instant(iso: '2026-01-02T00:00:00Z');

        $period = Period::of(
            start: $start,
            end: $end,
        );

        self::assertTrue($period->contains(instant: $end));
    }

    public function testContainsAcceptsInteriorMoment(): void
    {
        $period = Period::of(
            start: $this->instant(iso: '2026-01-01T00:00:00Z'),
            end: $this->instant(iso: '2026-01-02T00:00:00Z'),
        );

        self::assertTrue(
            $period->contains(instant: $this->instant(iso: '2026-01-01T12:00:00Z')),
        );
    }

    public function testContainsRejectsBeforeStart(): void
    {
        $period = Period::of(
            start: $this->instant(iso: '2026-01-01T00:00:00Z'),
            end: $this->instant(iso: '2026-01-02T00:00:00Z'),
        );

        self::assertFalse(
            $period->contains(instant: $this->instant(iso: '2025-12-31T23:59:59Z')),
        );
    }

    public function testContainsRejectsAfterEnd(): void
    {
        $period = Period::of(
            start: $this->instant(iso: '2026-01-01T00:00:00Z'),
            end: $this->instant(iso: '2026-01-02T00:00:00Z'),
        );

        self::assertFalse(
            $period->contains(instant: $this->instant(iso: '2026-01-02T00:00:01Z')),
        );
    }

    public function testOverlapsWhenPeriodsShareInterior(): void
    {
        $a = Period::of(
            start: $this->instant(iso: '2026-01-01T00:00:00Z'),
            end: $this->instant(iso: '2026-01-03T00:00:00Z'),
        );

        $b = Period::of(
            start: $this->instant(iso: '2026-01-02T00:00:00Z'),
            end: $this->instant(iso: '2026-01-04T00:00:00Z'),
        );

        self::assertTrue($a->overlaps(other: $b));
        self::assertTrue($b->overlaps(other: $a));
    }

    public function testOverlapsWhenBoundariesTouch(): void
    {
        $a = Period::of(
            start: $this->instant(iso: '2026-01-01T00:00:00Z'),
            end: $this->instant(iso: '2026-01-02T00:00:00Z'),
        );

        $b = Period::of(
            start: $this->instant(iso: '2026-01-02T00:00:00Z'),
            end: $this->instant(iso: '2026-01-03T00:00:00Z'),
        );

        self::assertTrue($a->overlaps(other: $b));
    }

    public function testOverlapsFalseWhenFullyBefore(): void
    {
        $a = Period::of(
            start: $this->instant(iso: '2026-01-01T00:00:00Z'),
            end: $this->instant(iso: '2026-01-02T00:00:00Z'),
        );

        $b = Period::of(
            start: $this->instant(iso: '2026-01-03T00:00:00Z'),
            end: $this->instant(iso: '2026-01-04T00:00:00Z'),
        );

        self::assertFalse($a->overlaps(other: $b));
    }

    public function testOverlapsFalseWhenFullyAfter(): void
    {
        $a = Period::of(
            start: $this->instant(iso: '2026-01-05T00:00:00Z'),
            end: $this->instant(iso: '2026-01-06T00:00:00Z'),
        );

        $b = Period::of(
            start: $this->instant(iso: '2026-01-01T00:00:00Z'),
            end: $this->instant(iso: '2026-01-02T00:00:00Z'),
        );

        self::assertFalse($a->overlaps(other: $b));
    }

    public function testLengthEqualsDifference(): void
    {
        $period = Period::of(
            start: $this->instant(iso: '2026-01-01T00:00:00Z'),
            end: $this->instant(iso: '2026-01-01T00:01:00Z'),
        );

        self::assertSame(60, $period->length()->seconds);
        self::assertFalse($period->length()->isNegative());
    }
}
