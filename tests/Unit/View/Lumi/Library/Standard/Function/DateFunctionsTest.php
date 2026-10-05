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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Humanizer\Humanizer;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\View\Lumi\Library\Standard\Function\DateFunctions;

class DateFunctionsTest extends TestCase
{
    private const int SECONDS_3D_4H = 3 * 86_400 + 4 * 3_600;

    private function functionsAt(
        string $reference,
    ): DateFunctions {
        $clock = new FixedClock(Instant::parse($reference));

        return new DateFunctions(
            clock: $clock,
            humanizer: new Humanizer($clock),
        );
    }

    public function testDateAcceptsInstantValue(): void
    {
        self::assertSame(
            '2026-07-16',
            (new DateFunctions())->date(
                format: 'Y-m-d',
                value: Instant::parse('2026-07-16T12:00:00Z'),
            ),
        );
    }

    public function testDateAcceptsLocalDateValue(): void
    {
        self::assertSame(
            '16/07/2026',
            (new DateFunctions())->date(
                format: 'd/m/Y',
                value: LocalDate::of(year: 2026, month: 7, day: 16),
            ),
        );
    }

    public function testDateFormatsWithExplicitUnixTimestamp(): void
    {
        self::assertSame('1970-01-01', (new DateFunctions())->date('Y-m-d', 0));
    }

    public function testDateUsesInjectedClockWhenNoValueProvided(): void
    {
        self::assertSame(
            '89-07-02 (14:02:00)',
            $this->functionsAt('1989-07-02T14:02:00+01:00')->date('y-m-d (H:i:s)'),
        );
    }

    public function testDateUsesSystemClockWhenNoDependenciesInjected(): void
    {
        self::assertMatchesRegularExpression('/^\d{4}$/', (new DateFunctions())->date('Y'));
    }

    /**
     * @return iterable<string, array{DurationInterface|int|string, string, string}>
     */
    public static function dateDurationMatrix(): iterable
    {
        yield 'default long from duration value' => [
            Duration::fromSeconds(self::SECONDS_3D_4H),
            'long',
            '3 days, 4 hours',
        ];

        yield 'short style' => [
            Duration::fromSeconds(self::SECONDS_3D_4H),
            'short',
            '3d 4h',
        ];

        yield 'narrow style' => [
            Duration::fromSeconds(self::SECONDS_3D_4H),
            'narrow',
            '3d4h',
        ];

        yield 'unknown style falls back to long' => [
            Duration::fromSeconds(self::SECONDS_3D_4H),
            'gibberish',
            '3 days, 4 hours',
        ];

        yield 'style is case insensitive' => [
            Duration::fromSeconds(self::SECONDS_3D_4H),
            'SHORT',
            '3d 4h',
        ];

        yield 'int seconds value' => [
            300,
            'long',
            '5 minutes',
        ];

        yield 'iso duration string value' => [
            'PT76H',
            'long',
            '3 days, 4 hours',
        ];
    }

    #[DataProvider('dateDurationMatrix')]
    public function testDateDuration(
        DurationInterface|int|string $value,
        string $style,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new DateFunctions())->dateDuration($value, $style),
        );
    }

    public function testDateLocalShiftsInstantIntoStringTimeZone(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            (new DateFunctions())->dateLocal(
                value: Instant::parse('2026-07-16T12:00:00Z'),
                timeZone: 'Europe/Copenhagen',
            ),
        );
    }

    public function testDateLocalAcceptsTimeZoneInterface(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            (new DateFunctions())->dateLocal(
                value: Instant::parse('2026-07-16T12:00:00Z'),
                timeZone: TimeZone::parse('Europe/Copenhagen'),
            ),
        );
    }

    public function testDateLocalExplicitFormat(): void
    {
        self::assertSame(
            '14:00',
            (new DateFunctions())->dateLocal(
                value: Instant::parse('2026-07-16T12:00:00Z'),
                timeZone: 'Europe/Copenhagen',
                format: 'H:i',
            ),
        );
    }

    public function testDateLocalFallsBackToSystemDefaultTimeZone(): void
    {
        $originalTimeZone = \date_default_timezone_get();

        \date_default_timezone_set('UTC');

        try {
            self::assertSame(
                '2026-07-16 12:00:00',
                (new DateFunctions())->dateLocal(
                    value: Instant::parse('2026-07-16T12:00:00Z'),
                ),
            );
        } finally {
            \date_default_timezone_set($originalTimeZone);
        }
    }

    public function testDateLocalCoercesStringValueInput(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            (new DateFunctions())->dateLocal(
                value: '2026-07-16T12:00:00Z',
                timeZone: 'Europe/Copenhagen',
            ),
        );
    }

    public function testDateNowDefaultFormat(): void
    {
        self::assertSame(
            '2026-07-16 12:34:56',
            $this->functionsAt('2026-07-16T12:34:56Z')->dateNow(),
        );
    }

    public function testDateNowExplicitFormat(): void
    {
        self::assertSame(
            '2026',
            $this->functionsAt('2026-07-16T12:34:56Z')->dateNow('Y'),
        );
    }

    public function testDateNowFallsBackToSystemClock(): void
    {
        self::assertMatchesRegularExpression('/^\d{4}$/', (new DateFunctions())->dateNow('Y'));
    }

    public function testDateTodayRendersIsoDate(): void
    {
        self::assertSame(
            '2026-07-16',
            $this->functionsAt('2026-07-16T12:34:56Z')->dateToday(),
        );
    }

    public function testNowReturnsUnixTimestampString(): void
    {
        self::assertSame(
            (string) Instant::parse('1989-07-02T14:02:00+01:00')->toUnixTimestamp(),
            $this->functionsAt('1989-07-02T14:02:00+01:00')->now(),
        );
    }

    public function testNowFallsBackToSystemClock(): void
    {
        self::assertMatchesRegularExpression('/^\d+$/', (new DateFunctions())->now());
    }

    public function testTimeNowDefaultFormat(): void
    {
        self::assertSame(
            '12:34:56',
            $this->functionsAt('2026-07-16T12:34:56Z')->timeNow(),
        );
    }

    public function testTimeNowExplicitFormat(): void
    {
        self::assertSame(
            '12:34',
            $this->functionsAt('2026-07-16T12:34:56Z')->timeNow('H:i'),
        );
    }

    public function testTimeNowFallsBackToSystemClock(): void
    {
        self::assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', (new DateFunctions())->timeNow());
    }
}
