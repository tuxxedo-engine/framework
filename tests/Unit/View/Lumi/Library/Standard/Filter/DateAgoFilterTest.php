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

namespace Unit\View\Lumi\Library\Standard\Filter;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Humanizer\Humanizer;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\View\Lumi\Library\Standard\Filter\DateAgoFilter;

class DateAgoFilterTest extends TestCase
{
    public function testHumanizesPastInstant(): void
    {
        $reference = Instant::parse('2026-07-16T12:00:00Z');
        $filter = new DateAgoFilter(new Humanizer(new FixedClock($reference)));

        $moment = $reference->minus(Duration::fromHours(3));

        self::assertSame(
            '3 hours ago',
            $filter->call($moment, static fn () => new StubRuntimeContext()),
        );
    }

    public function testHumanizesFutureInstant(): void
    {
        $reference = Instant::parse('2026-07-16T12:00:00Z');
        $filter = new DateAgoFilter(new Humanizer(new FixedClock($reference)));

        $moment = $reference->plus(Duration::fromMinutes(5));

        self::assertSame(
            'in 5 minutes',
            $filter->call($moment, static fn () => new StubRuntimeContext()),
        );
    }

    public function testHumanizesCoercedStringInput(): void
    {
        $reference = Instant::parse('2026-07-16T12:00:00Z');
        $filter = new DateAgoFilter(new Humanizer(new FixedClock($reference)));

        self::assertSame(
            '1 hour ago',
            $filter->call('2026-07-16T11:00:00Z', static fn () => new StubRuntimeContext()),
        );
    }

    public function testDefaultsToSystemClockWhenNoDependenciesInjected(): void
    {
        $filter = new DateAgoFilter();

        $result = $filter->call(Instant::now(), static fn () => new StubRuntimeContext());

        self::assertSame('just now', $result);
    }
}
