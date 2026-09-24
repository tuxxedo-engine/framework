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
use Tuxxedo\Temporal\Instant;
use Tuxxedo\View\Lumi\Library\Standard\Filter\DateUtcFilter;

class DateUtcFilterTest extends TestCase
{
    public function testShiftsNonUtcInstantToUtc(): void
    {
        self::assertSame(
            '2026-07-16 11:00:00',
            (new DateUtcFilter())->call(
                Instant::parse('2026-07-16T13:00:00+02:00'),
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testHandlesAlreadyUtcInstant(): void
    {
        self::assertSame(
            '2026-07-16 12:00:00',
            (new DateUtcFilter())->call(
                Instant::parse('2026-07-16T12:00:00Z'),
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCoercesUnixTimestampAsUtc(): void
    {
        self::assertSame(
            '2026-07-16 12:00:00',
            (new DateUtcFilter())->call(1_784_203_200, static fn () => new StubRuntimeContext()),
        );
    }
}
