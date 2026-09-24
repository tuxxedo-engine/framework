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
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\View\Lumi\Library\Standard\Filter\TimeIsoFilter;

class TimeIsoFilterTest extends TestCase
{
    public function testFormatsLocalTimeAsIso(): void
    {
        self::assertSame(
            '09:30:15',
            (new TimeIsoFilter())->call(
                LocalTime::of(hour: 9, minute: 30, second: 15),
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testExtractsTimeFromInstant(): void
    {
        self::assertSame(
            '12:00:00',
            (new TimeIsoFilter())->call(
                Instant::parse('2026-07-16T12:00:00Z'),
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCoercesStringInput(): void
    {
        self::assertSame(
            '12:00:00',
            (new TimeIsoFilter())->call(
                '2026-07-16T12:00:00Z',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
