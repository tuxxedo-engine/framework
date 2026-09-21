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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Temporal\DayOfWeek;
use Tuxxedo\Temporal\Instant;

class DayOfWeekTest extends TestCase
{
    /**
     * @return iterable<string, array{string, DayOfWeek}>
     */
    public static function isoDayOfWeekMatrix(): iterable
    {
        yield 'monday' => [
            '2026-01-05T00:00:00Z',
            DayOfWeek::MONDAY,
        ];

        yield 'tuesday' => [
            '2026-01-06T00:00:00Z',
            DayOfWeek::TUESDAY,
        ];

        yield 'wednesday' => [
            '2026-01-07T00:00:00Z',
            DayOfWeek::WEDNESDAY,
        ];

        yield 'thursday' => [
            '2026-01-08T00:00:00Z',
            DayOfWeek::THURSDAY,
        ];

        yield 'friday' => [
            '2026-01-09T00:00:00Z',
            DayOfWeek::FRIDAY,
        ];

        yield 'saturday' => [
            '2026-01-10T00:00:00Z',
            DayOfWeek::SATURDAY,
        ];

        yield 'sunday' => [
            '2026-01-11T00:00:00Z',
            DayOfWeek::SUNDAY,
        ];
    }

    #[DataProvider('isoDayOfWeekMatrix')]
    public function testFromInstantMatchesIsoWeekday(
        string $iso,
        DayOfWeek $expected,
    ): void {
        $instant = Instant::parse(
            input: $iso,
        );

        self::assertSame($expected, DayOfWeek::fromInstant(instant: $instant));
    }

    public function testCasesUseIsoBackingValues(): void
    {
        self::assertSame(1, DayOfWeek::MONDAY->value);
        self::assertSame(7, DayOfWeek::SUNDAY->value);
    }
}
