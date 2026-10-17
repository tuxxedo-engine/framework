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
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Loader\FlatFileLoader;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\MissingKeyMode;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Humanizer\Humanizer;
use Tuxxedo\Temporal\Humanizer\Message\MessageFormatterInterface;
use Tuxxedo\Temporal\Humanizer\Message\TranslatorMessageFormatter;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\SystemClock;
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
            humanizer: new Humanizer(
                clock: $clock,
                messageFormatter: $this->makeMessageFormatter(),
            ),
        );
    }

    private function functionsWithSystemClock(): DateFunctions
    {
        return $this->functionsWith(
            clock: new SystemClock(),
        );
    }

    private function functionsWith(
        ClockInterface $clock,
    ): DateFunctions {
        return new DateFunctions(
            clock: $clock,
            humanizer: new Humanizer(
                clock: $clock,
                messageFormatter: $this->makeMessageFormatter(),
            ),
        );
    }

    private function makeMessageFormatter(): MessageFormatterInterface
    {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse('en-US'),
            missingKeyMode: MissingKeyMode::PASSTHROUGH,
        );

        return new TranslatorMessageFormatter(
            translator: new Translator(
                config: $config,
                loader: new FlatFileLoader(
                    rootDirectory: \dirname(__DIR__, 7) . '/resources/intl',
                ),
                resolver: new StaticLocaleResolver(
                    config: $config,
                ),
                pluralSelector: new PluralSelector(),
            ),
        );
    }

    public function testDateAcceptsInstantValue(): void
    {
        self::assertSame(
            '2026-07-16',
            $this->functionsWithSystemClock()->date(
                context: new StubRuntimeContext(),
                format: 'Y-m-d',
                value: Instant::parse('2026-07-16T12:00:00Z'),
            ),
        );
    }

    public function testDateAcceptsLocalDateValue(): void
    {
        self::assertSame(
            '16/07/2026',
            $this->functionsWithSystemClock()->date(
                context: new StubRuntimeContext(),
                format: 'd/m/Y',
                value: LocalDate::of(year: 2026, month: 7, day: 16),
            ),
        );
    }

    public function testDateFormatsWithExplicitUnixTimestamp(): void
    {
        self::assertSame('1970-01-01', $this->functionsWithSystemClock()->date(new StubRuntimeContext(), 'Y-m-d', 0));
    }

    public function testDateUsesInjectedClockWhenNoValueProvided(): void
    {
        self::assertSame(
            '89-07-02 (14:02:00)',
            $this->functionsAt('1989-07-02T14:02:00+01:00')->date(new StubRuntimeContext(), 'y-m-d (H:i:s)'),
        );
    }

    public function testDateUsesSystemClockWhenNoDependenciesInjected(): void
    {
        self::assertMatchesRegularExpression('/^\d{4}$/', $this->functionsWithSystemClock()->date(new StubRuntimeContext(), 'Y'));
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
            $this->functionsWithSystemClock()->dateDuration($value, $style),
        );
    }

    public function testDateLocalShiftsInstantIntoStringTimeZone(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            $this->functionsWithSystemClock()->dateLocal(
                context: new StubRuntimeContext(),
                value: Instant::parse('2026-07-16T12:00:00Z'),
                timeZone: 'Europe/Copenhagen',
            ),
        );
    }

    public function testDateLocalAcceptsTimeZoneInterface(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            $this->functionsWithSystemClock()->dateLocal(
                context: new StubRuntimeContext(),
                value: Instant::parse('2026-07-16T12:00:00Z'),
                timeZone: TimeZone::parse('Europe/Copenhagen'),
            ),
        );
    }

    public function testDateLocalExplicitFormat(): void
    {
        self::assertSame(
            '14:00',
            $this->functionsWithSystemClock()->dateLocal(
                context: new StubRuntimeContext(),
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
                $this->functionsWithSystemClock()->dateLocal(
                    context: new StubRuntimeContext(),
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
            $this->functionsWithSystemClock()->dateLocal(
                context: new StubRuntimeContext(),
                value: '2026-07-16T12:00:00Z',
                timeZone: 'Europe/Copenhagen',
            ),
        );
    }

    public function testDateNowDefaultFormat(): void
    {
        self::assertSame(
            '2026-07-16 12:34:56',
            $this->functionsAt('2026-07-16T12:34:56Z')->dateNow(new StubRuntimeContext()),
        );
    }

    public function testDateNowExplicitFormat(): void
    {
        self::assertSame(
            '2026',
            $this->functionsAt('2026-07-16T12:34:56Z')->dateNow(new StubRuntimeContext(), 'Y'),
        );
    }

    public function testDateNowFallsBackToSystemClock(): void
    {
        self::assertMatchesRegularExpression('/^\d{4}$/', $this->functionsWithSystemClock()->dateNow(new StubRuntimeContext(), 'Y'));
    }

    public function testDateTodayRendersIsoDate(): void
    {
        self::assertSame(
            '2026-07-16',
            $this->functionsAt('2026-07-16T12:34:56Z')->dateToday(new StubRuntimeContext()),
        );
    }

    public function testTimeZoneDirectiveAppliesToNowHelpersAndInstants(): void
    {
        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.timezone' => 'Europe/Copenhagen',
            ],
        );

        $functions = $this->functionsAt('2026-07-16T22:30:00Z');

        self::assertSame('2026-07-17 00:30:00', $functions->dateNow($context));
        self::assertSame('00:30:00', $functions->timeNow($context));
        self::assertSame('2026-07-17', $functions->dateToday($context));
        self::assertSame('14:00', $functions->date($context, 'H:i', Instant::parse('2026-07-16T12:00:00Z')));
        self::assertSame('2026-07-16', $functions->date($context, 'Y-m-d', LocalDate::of(year: 2026, month: 7, day: 16)));
    }

    public function testDateLocalPrefersTheArgumentThenTheTimeZoneDirective(): void
    {
        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.timezone' => 'Europe/Copenhagen',
            ],
        );

        $functions = $this->functionsWithSystemClock();
        $value = Instant::parse('2026-07-16T12:00:00Z');

        self::assertSame('14:00', $functions->dateLocal($context, $value, null, 'H:i'));
        self::assertSame('08:00', $functions->dateLocal($context, $value, 'America/New_York', 'H:i'));
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
        self::assertMatchesRegularExpression('/^\d+$/', $this->functionsWithSystemClock()->now());
    }

    public function testTimeNowDefaultFormat(): void
    {
        self::assertSame(
            '12:34:56',
            $this->functionsAt('2026-07-16T12:34:56Z')->timeNow(new StubRuntimeContext()),
        );
    }

    public function testTimeNowExplicitFormat(): void
    {
        self::assertSame(
            '12:34',
            $this->functionsAt('2026-07-16T12:34:56Z')->timeNow(new StubRuntimeContext(), 'H:i'),
        );
    }

    public function testTimeNowFallsBackToSystemClock(): void
    {
        self::assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $this->functionsWithSystemClock()->timeNow(new StubRuntimeContext()));
    }
}
