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
use Tuxxedo\Temporal\AdvancingClock;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\Instant;

class AdvancingClockTest extends TestCase
{
    public function testFirstCallReturnsStart(): void
    {
        $start = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );
        $clock = new AdvancingClock(
            start: $start,
            step: Duration::fromSeconds(seconds: 1),
        );

        self::assertTrue($clock->now()->equals(other: $start));
    }

    public function testEachCallAdvancesByStep(): void
    {
        $start = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );
        $clock = new AdvancingClock(
            start: $start,
            step: Duration::fromSeconds(seconds: 5),
        );

        $first = $clock->now();
        $second = $clock->now();
        $third = $clock->now();

        self::assertSame($first->toUnixTimestamp(), $start->toUnixTimestamp());
        self::assertSame($second->toUnixTimestamp(), $start->toUnixTimestamp() + 5);
        self::assertSame($third->toUnixTimestamp(), $start->toUnixTimestamp() + 10);
    }

    public function testNegativeStepAdvancesBackward(): void
    {
        $start = Instant::parse(
            input: '2026-01-01T00:01:00Z',
        );
        $clock = new AdvancingClock(
            start: $start,
            step: Duration::fromSeconds(seconds: 10)->negate(),
        );

        $first = $clock->now();
        $second = $clock->now();

        self::assertSame($first->toUnixTimestamp(), $start->toUnixTimestamp());
        self::assertSame($second->toUnixTimestamp(), $start->toUnixTimestamp() - 10);
    }
}
