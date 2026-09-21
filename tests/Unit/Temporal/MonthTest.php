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
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\Month;

class MonthTest extends TestCase
{
    /**
     * @return iterable<string, array{string, Month}>
     */
    public static function monthMatrix(): iterable
    {
        yield 'january' => [
            '2026-01-15T00:00:00Z',
            Month::JANUARY,
        ];

        yield 'february' => [
            '2026-02-15T00:00:00Z',
            Month::FEBRUARY,
        ];

        yield 'march' => [
            '2026-03-15T00:00:00Z',
            Month::MARCH,
        ];

        yield 'december' => [
            '2026-12-31T23:59:59Z',
            Month::DECEMBER,
        ];
    }

    #[DataProvider('monthMatrix')]
    public function testFromInstantMatchesCalendarMonth(
        string $iso,
        Month $expected,
    ): void {
        $instant = Instant::parse(
            input: $iso,
        );

        self::assertSame($expected, Month::fromInstant(instant: $instant));
    }

    /**
     * @return iterable<string, array{Month, int, int}>
     */
    public static function lengthMatrix(): iterable
    {
        yield 'january always 31' => [
            Month::JANUARY,
            2026,
            31,
        ];

        yield 'march always 31' => [
            Month::MARCH,
            2026,
            31,
        ];

        yield 'may always 31' => [
            Month::MAY,
            2026,
            31,
        ];

        yield 'july always 31' => [
            Month::JULY,
            2026,
            31,
        ];

        yield 'august always 31' => [
            Month::AUGUST,
            2026,
            31,
        ];

        yield 'october always 31' => [
            Month::OCTOBER,
            2026,
            31,
        ];

        yield 'december always 31' => [
            Month::DECEMBER,
            2026,
            31,
        ];

        yield 'april always 30' => [
            Month::APRIL,
            2026,
            30,
        ];

        yield 'june always 30' => [
            Month::JUNE,
            2026,
            30,
        ];

        yield 'september always 30' => [
            Month::SEPTEMBER,
            2026,
            30,
        ];

        yield 'november always 30' => [
            Month::NOVEMBER,
            2026,
            30,
        ];

        yield 'february common year 28' => [
            Month::FEBRUARY,
            2026,
            28,
        ];

        yield 'february divisible-by-4 leap 29' => [
            Month::FEBRUARY,
            2028,
            29,
        ];

        yield 'february century non-leap 28' => [
            Month::FEBRUARY,
            2100,
            28,
        ];

        yield 'february 400-year leap 29' => [
            Month::FEBRUARY,
            2000,
            29,
        ];
    }

    #[DataProvider('lengthMatrix')]
    public function testLengthInDaysReturnsCalendarLength(
        Month $month,
        int $year,
        int $expected,
    ): void {
        self::assertSame($expected, $month->lengthInDays(year: $year));
    }

    public function testCasesUseCalendarBackingValues(): void
    {
        self::assertSame(1, Month::JANUARY->value);
        self::assertSame(12, Month::DECEMBER->value);
    }
}
