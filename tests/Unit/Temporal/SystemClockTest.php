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
use Tuxxedo\Temporal\SystemClock;

class SystemClockTest extends TestCase
{
    public function testNowReturnsCurrentTime(): void
    {
        $before = \time();
        $now = (new SystemClock())->now();
        $after = \time();

        self::assertGreaterThanOrEqual(
            $before,
            $now->getTimestamp(),
        );

        self::assertLessThanOrEqual(
            $after,
            $now->getTimestamp(),
        );
    }

    public function testNowReturnsFreshInstanceEachCall(): void
    {
        $clock = new SystemClock();

        self::assertNotSame(
            $clock->now(),
            $clock->now(),
        );
    }
}
