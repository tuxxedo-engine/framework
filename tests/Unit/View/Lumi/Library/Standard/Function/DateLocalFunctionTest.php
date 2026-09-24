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
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\View\Lumi\Library\Standard\Function\DateLocalFunction;

class DateLocalFunctionTest extends TestCase
{
    public function testShiftsInstantIntoStringTimeZone(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            (new DateLocalFunction())->call(
                [
                    Instant::parse('2026-07-16T12:00:00Z'),
                    'Europe/Copenhagen',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testAcceptsTimeZoneInterface(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            (new DateLocalFunction())->call(
                [
                    Instant::parse('2026-07-16T12:00:00Z'),
                    TimeZone::parse('Europe/Copenhagen'),
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testExplicitFormat(): void
    {
        self::assertSame(
            '14:00',
            (new DateLocalFunction())->call(
                [
                    Instant::parse('2026-07-16T12:00:00Z'),
                    'Europe/Copenhagen',
                    'H:i',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testFallsBackToSystemDefaultTimeZone(): void
    {
        $originalTimeZone = \date_default_timezone_get();

        \date_default_timezone_set('UTC');

        try {
            self::assertSame(
                '2026-07-16 12:00:00',
                (new DateLocalFunction())->call(
                    [
                        Instant::parse('2026-07-16T12:00:00Z'),
                    ],
                    static fn () => new StubRuntimeContext(),
                ),
            );
        } finally {
            \date_default_timezone_set($originalTimeZone);
        }
    }

    public function testCoercesStringValueInput(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            (new DateLocalFunction())->call(
                [
                    '2026-07-16T12:00:00Z',
                    'Europe/Copenhagen',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
