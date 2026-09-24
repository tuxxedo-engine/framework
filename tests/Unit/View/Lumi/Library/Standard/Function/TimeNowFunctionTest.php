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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\View\Lumi\Library\Standard\Function\TimeNowFunction;

class TimeNowFunctionTest extends TestCase
{
    public function testDefaultFormat(): void
    {
        $clock = new FixedClock(Instant::parse('2026-07-16T12:34:56Z'));

        self::assertSame(
            '12:34:56',
            (new TimeNowFunction($clock))->call([], static fn () => new StubRuntimeContext()),
        );
    }

    public function testExplicitFormat(): void
    {
        $clock = new FixedClock(Instant::parse('2026-07-16T12:34:56Z'));

        self::assertSame(
            '12:34',
            (new TimeNowFunction($clock))->call(
                [
                    'H:i',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testFallsBackToSystemClock(): void
    {
        $result = (new TimeNowFunction())->call([], static fn () => new StubRuntimeContext());

        self::assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $result);
    }
}
