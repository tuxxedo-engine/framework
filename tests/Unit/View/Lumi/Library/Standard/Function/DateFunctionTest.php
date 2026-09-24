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
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\View\Lumi\Library\Standard\Function\DateFunction;

class DateFunctionTest extends TestCase
{
    public function testCallAcceptsInstantValue(): void
    {
        self::assertSame(
            '2026-07-16',
            (new DateFunction())->call(
                [
                    'Y-m-d',
                    Instant::parse('2026-07-16T12:00:00Z'),
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallAcceptsLocalDateValue(): void
    {
        self::assertSame(
            '16/07/2026',
            (new DateFunction())->call(
                [
                    'd/m/Y',
                    LocalDate::of(year: 2026, month: 7, day: 16),
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallFormatsDateWithExplicitTimestamp(): void
    {
        self::assertSame(
            '1970-01-01',
            (new DateFunction())->call(
                [
                    'Y-m-d',
                    0,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallUsesCurrentTimeWhenNoTimestampProvided(): void
    {
        $result = (new DateFunction())->call(
            [
                'Y',
            ],
            static fn () => new StubRuntimeContext(),
        );

        self::assertMatchesRegularExpression('/^\d{4}$/', $result);
    }

    public function testCallWithCustomClock(): void
    {
        $clock = new FixedClock(Instant::parse('1989-07-02T14:02:00+01:00'));

        $result = (new DateFunction($clock))->call(
            [
                'y-m-d (H:i:s)',
            ],
            static fn () => new StubRuntimeContext(),
        );

        self::assertSame('89-07-02 (14:02:00)', $result);
    }
}
