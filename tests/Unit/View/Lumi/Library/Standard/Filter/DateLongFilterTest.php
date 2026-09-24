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
use Tuxxedo\View\Lumi\Library\Standard\Filter\DateLongFilter;

class DateLongFilterTest extends TestCase
{
    public function testFormatsInstantWithLongPreset(): void
    {
        self::assertSame(
            'Thursday, July 16, 2026 12:00 PM',
            (new DateLongFilter())->call(
                Instant::parse('2026-07-16T12:00:00Z'),
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
