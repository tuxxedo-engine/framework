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

namespace Unit\Http\Client\Response;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\Response\ClientResponseTimings;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;

class ClientResponseTimingsTest extends TestCase
{
    private static function microseconds(
        DurationInterface $duration,
    ): int {
        return \intdiv($duration->seconds * 1_000_000_000 + $duration->nanoseconds, 1_000);
    }

    public function testFromInfoSplitsCumulativeTimesIntoPhases(): void
    {
        $timings = ClientResponseTimings::fromInfo(
            [
                'namelookup_time_us' => 20_000,
                'connect_time_us' => 120_000,
                'appconnect_time_us' => 300_000,
                'pretransfer_time_us' => 310_000,
                'starttransfer_time_us' => 1_110_000,
                'total_time_us' => 1_200_000,
            ],
        );

        self::assertSame(20_000, self::microseconds($timings->dns));
        self::assertSame(100_000, self::microseconds($timings->connect));
        self::assertSame(180_000, self::microseconds($timings->tls));
        self::assertSame(800_000, self::microseconds($timings->waiting));
        self::assertSame(90_000, self::microseconds($timings->download));
        self::assertSame(1_200_000, self::microseconds($timings->total));
    }

    public function testFromInfoWithoutTls(): void
    {
        $timings = ClientResponseTimings::fromInfo(
            [
                'namelookup_time_us' => 10,
                'connect_time_us' => 30,
                'appconnect_time_us' => 0,
                'pretransfer_time_us' => 40,
                'starttransfer_time_us' => 100,
                'total_time_us' => 150,
            ],
        );

        self::assertSame(0, self::microseconds($timings->tls));
    }

    public function testFromInfoClampsInconsistentValuesAndUsesTheGivenTotal(): void
    {
        $timings = ClientResponseTimings::fromInfo(
            info: [
                'namelookup_time_us' => 50,
                'connect_time_us' => 10,
                'total_time_us' => 'not a number',
            ],
            totalMicroseconds: 5_000,
        );

        self::assertSame(0, self::microseconds($timings->connect));
        self::assertSame(0, self::microseconds($timings->download));
        self::assertSame(5_000, self::microseconds($timings->total));
    }

    public function testConstructor(): void
    {
        $duration = Duration::fromMicroseconds(1);
        $timings = new ClientResponseTimings($duration, $duration, $duration, $duration, $duration, $duration);

        self::assertSame($duration, $timings->total);
    }
}
