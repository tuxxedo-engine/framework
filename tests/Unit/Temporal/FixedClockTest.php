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
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class FixedClockTest extends TestCase
{
    public function testNowReturnsConstructorInstant(): void
    {
        $instant = Instant::parse(
            input: '2026-01-01T00:00:00Z',
        );
        $clock = new FixedClock(
            instant: $instant,
        );

        self::assertSame($instant, $clock->now());
    }

    public function testNowReturnsSameInstanceAcrossCalls(): void
    {
        $clock = new FixedClock(
            instant: Instant::parse(
                input: '2026-01-01T00:00:00Z',
            ),
        );

        self::assertSame($clock->now(), $clock->now());
    }
}
