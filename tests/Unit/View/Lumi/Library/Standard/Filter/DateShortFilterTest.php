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
use Tuxxedo\View\Lumi\Library\Standard\Filter\DateShortFilter;

class DateShortFilterTest extends TestCase
{
    public function testFormatsInstantWithShortPreset(): void
    {
        self::assertSame(
            '2026-07-16 12:00',
            (new DateShortFilter())->call(
                Instant::parse('2026-07-16T12:00:00Z'),
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCoercesStringInput(): void
    {
        self::assertSame(
            '2026-07-16 12:00',
            (new DateShortFilter())->call(
                '2026-07-16T12:00:00Z',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
