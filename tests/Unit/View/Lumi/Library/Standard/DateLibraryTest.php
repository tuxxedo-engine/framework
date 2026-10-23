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

namespace Unit\View\Lumi\Library\Standard;

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
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\Temporal\LocalTimeInterface;
use Tuxxedo\Temporal\SystemClock;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\View\Lumi\Library\Standard\DateLibrary;

class DateLibraryTest extends TestCase
{
    private const int SECONDS_3D_4H = 3 * 86_400 + 4 * 3_600;

    private function libraryAt(
        string $reference,
    ): DateLibrary {
        $clock = new FixedClock(Instant::parse($reference));

        return new DateLibrary(
            clock: $clock,
            humanizer: new Humanizer(
                clock: $clock,
                messageFormatter: $this->makeMessageFormatter(),
            ),
        );
    }

    private function libraryWithSystemClock(): DateLibrary
    {
        return $this->libraryWith(
            clock: new SystemClock(),
        );
    }

    private function libraryWith(
        ClockInterface $clock,
    ): DateLibrary {
        return new DateLibrary(
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
                    rootDirectory: \dirname(__DIR__, 6) . '/resources/intl',
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
            $this->libraryWithSystemClock()->date(
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
            $this->libraryWithSystemClock()->date(
                context: new StubRuntimeContext(),
                format: 'd/m/Y',
                value: LocalDate::of(year: 2026, month: 7, day: 16),
            ),
        );
    }

    public function testDateFormatsWithExplicitUnixTimestamp(): void
    {
        self::assertSame('1970-01-01', $this->libraryWithSystemClock()->date(new StubRuntimeContext(), 'Y-m-d', 0));
    }

    public function testDateUsesInjectedClockWhenNoValueProvided(): void
    {
        self::assertSame(
            '89-07-02 (14:02:00)',
            $this->libraryAt('1989-07-02T14:02:00+01:00')->date(new StubRuntimeContext(), 'y-m-d (H:i:s)'),
        );
    }

    public function testDateUsesSystemClockWhenNoDependenciesInjected(): void
    {
        self::assertMatchesRegularExpression('/^\d{4}$/', $this->libraryWithSystemClock()->date(new StubRuntimeContext(), 'Y'));
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
            $this->libraryWithSystemClock()->dateDuration(new StubRuntimeContext(), $value, $style),
        );
    }

    public function testDateDurationFollowsTheLocaleDirective(): void
    {
        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'da-DK',
            ],
        );

        self::assertSame('2 timer', $this->libraryWithSystemClock()->dateDuration($context, 7200));
        self::assertSame('0 sekunder', $this->libraryWithSystemClock()->dateDuration($context, 0));
    }

    public function testDateLocalShiftsInstantIntoStringTimeZone(): void
    {
        self::assertSame(
            '2026-07-16 14:00:00',
            $this->libraryWithSystemClock()->dateLocal(
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
            $this->libraryWithSystemClock()->dateLocal(
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
            $this->libraryWithSystemClock()->dateLocal(
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
                $this->libraryWithSystemClock()->dateLocal(
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
            $this->libraryWithSystemClock()->dateLocal(
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
            $this->libraryAt('2026-07-16T12:34:56Z')->dateNow(new StubRuntimeContext()),
        );
    }

    public function testDateNowExplicitFormat(): void
    {
        self::assertSame(
            '2026',
            $this->libraryAt('2026-07-16T12:34:56Z')->dateNow(new StubRuntimeContext(), 'Y'),
        );
    }

    public function testDateNowFallsBackToSystemClock(): void
    {
        self::assertMatchesRegularExpression('/^\d{4}$/', $this->libraryWithSystemClock()->dateNow(new StubRuntimeContext(), 'Y'));
    }

    public function testDateTodayRendersIsoDate(): void
    {
        self::assertSame(
            '2026-07-16',
            $this->libraryAt('2026-07-16T12:34:56Z')->dateToday(new StubRuntimeContext()),
        );
    }

    public function testTimeZoneDirectiveAppliesToNowHelpersAndInstants(): void
    {
        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.timezone' => 'Europe/Copenhagen',
            ],
        );

        $library = $this->libraryAt('2026-07-16T22:30:00Z');

        self::assertSame('2026-07-17 00:30:00', $library->dateNow($context));
        self::assertSame('00:30:00', $library->timeNow($context));
        self::assertSame('2026-07-17', $library->dateToday($context));
        self::assertSame('14:00', $library->date($context, 'H:i', Instant::parse('2026-07-16T12:00:00Z')));
        self::assertSame('2026-07-16', $library->date($context, 'Y-m-d', LocalDate::of(year: 2026, month: 7, day: 16)));
    }

    public function testDateLocalPrefersTheArgumentThenTheTimeZoneDirective(): void
    {
        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.timezone' => 'Europe/Copenhagen',
            ],
        );

        $library = $this->libraryWithSystemClock();
        $value = Instant::parse('2026-07-16T12:00:00Z');

        self::assertSame('14:00', $library->dateLocal($context, $value, null, 'H:i'));
        self::assertSame('08:00', $library->dateLocal($context, $value, 'America/New_York', 'H:i'));
    }

    public function testNowReturnsUnixTimestampString(): void
    {
        self::assertSame(
            (string) Instant::parse('1989-07-02T14:02:00+01:00')->toUnixTimestamp(),
            $this->libraryAt('1989-07-02T14:02:00+01:00')->now(),
        );
    }

    public function testNowFallsBackToSystemClock(): void
    {
        self::assertMatchesRegularExpression('/^\d+$/', $this->libraryWithSystemClock()->now());
    }

    public function testTimeNowDefaultFormat(): void
    {
        self::assertSame(
            '12:34:56',
            $this->libraryAt('2026-07-16T12:34:56Z')->timeNow(new StubRuntimeContext()),
        );
    }

    public function testTimeNowExplicitFormat(): void
    {
        self::assertSame(
            '12:34',
            $this->libraryAt('2026-07-16T12:34:56Z')->timeNow(new StubRuntimeContext(), 'H:i'),
        );
    }

    public function testTimeNowFallsBackToSystemClock(): void
    {
        self::assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $this->libraryWithSystemClock()->timeNow(new StubRuntimeContext()));
    }

    public function testDateAgoHumanizesPastInstant(): void
    {
        $library = $this->libraryAt('2026-07-16T12:00:00Z');
        $moment = Instant::parse('2026-07-16T12:00:00Z')->minus(Duration::fromHours(3));

        self::assertSame('3 hours ago', $library->dateAgo(new StubRuntimeContext(), $moment));
    }

    public function testDateAgoHumanizesFutureInstant(): void
    {
        $library = $this->libraryAt('2026-07-16T12:00:00Z');
        $moment = Instant::parse('2026-07-16T12:00:00Z')->plus(Duration::fromMinutes(5));

        self::assertSame('in 5 minutes', $library->dateAgo(new StubRuntimeContext(), $moment));
    }

    public function testDateAgoHumanizesCoercedStringInput(): void
    {
        $library = $this->libraryAt('2026-07-16T12:00:00Z');

        self::assertSame('1 hour ago', $library->dateAgo(new StubRuntimeContext(), '2026-07-16T11:00:00Z'));
    }

    public function testDateAgoFollowsTheLocaleDirective(): void
    {
        $library = $this->libraryAt('2026-07-16T12:00:00Z');
        $moment = Instant::parse('2026-07-16T09:00:00Z');

        $danish = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'da-DK',
            ],
        );

        self::assertSame('for 3 timer siden', $library->dateAgo($danish, $moment));
        self::assertSame('3 hours ago', $library->dateAgo(new StubRuntimeContext(), $moment));
    }

    /**
     * @return iterable<string, array{InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string, string}>
     */
    public static function isoInputs(): iterable
    {
        yield 'InstantInterface' => [
            Instant::parse('2026-07-16T12:00:00Z'),
            '2026-07-16T12:00:00+00:00',
        ];

        yield 'LocalDateInterface' => [
            LocalDate::of(year: 2026, month: 7, day: 16),
            '2026-07-16',
        ];

        yield 'LocalTimeInterface' => [
            LocalTime::of(hour: 9, minute: 30, second: 15),
            '09:30:15',
        ];

        yield 'DateTimeImmutable' => [
            new \DateTimeImmutable('2026-07-16T12:00:00Z'),
            '2026-07-16T12:00:00+00:00',
        ];

        yield 'DateTime mutable' => [
            new \DateTime('2026-07-16T12:00:00Z'),
            '2026-07-16T12:00:00+00:00',
        ];

        yield 'unix timestamp' => [
            1_784_203_200,
            '2026-07-16T12:00:00+00:00',
        ];

        yield 'iso string' => [
            '2026-07-16T12:00:00Z',
            '2026-07-16T12:00:00+00:00',
        ];
    }

    #[DataProvider('isoInputs')]
    public function testDateIsoProducesIsoAcrossInputTypes(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        string $expected,
    ): void {
        self::assertSame($expected, $this->libraryAt('2026-07-16T12:00:00Z')->dateIso($value));
    }

    public function testDateFullFormatsInstantWithFullPreset(): void
    {
        self::assertSame(
            'Thursday, July 16, 2026 12:00 PM',
            $this->libraryAt('2026-07-16T12:00:00Z')->dateFull(new StubRuntimeContext(), Instant::parse('2026-07-16T12:00:00Z')),
        );
    }

    public function testDateLongAndMediumFormatInstantWithTheirPresets(): void
    {
        $library = $this->libraryAt('2026-07-16T12:00:00Z');
        $instant = Instant::parse('2026-07-16T12:00:00Z');

        self::assertSame('July 16, 2026 12:00 PM', $library->dateLong(new StubRuntimeContext(), $instant));
        self::assertSame('Jul 16, 2026 12:00 PM', $library->dateMedium(new StubRuntimeContext(), $instant));
    }

    public function testPresetsShowOnlyTheDateOrTimePartForLocalValues(): void
    {
        $library = $this->libraryAt('2026-07-16T12:00:00Z');

        self::assertSame('Thursday, July 16, 2026', $library->dateFull(new StubRuntimeContext(), LocalDate::of(2026, 7, 16)));
        self::assertSame('2026-07-16', $library->dateShort(new StubRuntimeContext(), LocalDate::of(2026, 7, 16)));
        self::assertSame('2:30 PM', $library->dateLong(new StubRuntimeContext(), LocalTime::of(14, 30)));
        self::assertSame('14:30', $library->dateShort(new StubRuntimeContext(), LocalTime::of(14, 30)));
    }

    public function testDateShortFormatsInstantWithShortPreset(): void
    {
        self::assertSame(
            '2026-07-16 12:00',
            $this->libraryAt('2026-07-16T12:00:00Z')->dateShort(new StubRuntimeContext(), Instant::parse('2026-07-16T12:00:00Z')),
        );
    }

    public function testDateShortCoercesStringInput(): void
    {
        self::assertSame(
            '2026-07-16 12:00',
            $this->libraryAt('2026-07-16T12:00:00Z')->dateShort(new StubRuntimeContext(), '2026-07-16T12:00:00Z'),
        );
    }

    public function testDateShortFollowsTheTimeZoneDirective(): void
    {
        self::assertSame(
            '2026-07-16 14:00',
            $this->libraryAt('2026-07-16T12:00:00Z')->dateShort(
                new StubRuntimeContext(
                    directives: [
                        'tuxxedo.timezone' => 'Europe/Copenhagen',
                    ],
                ),
                Instant::parse('2026-07-16T12:00:00Z'),
            ),
        );
    }

    public function testDateUtcShiftsNonUtcInstantToUtc(): void
    {
        self::assertSame(
            '2026-07-16 11:00:00',
            $this->libraryAt('2026-07-16T12:00:00Z')->dateUtc(Instant::parse('2026-07-16T13:00:00+02:00')),
        );
    }

    public function testDateUtcHandlesAlreadyUtcInstant(): void
    {
        self::assertSame(
            '2026-07-16 12:00:00',
            $this->libraryAt('2026-07-16T12:00:00Z')->dateUtc(Instant::parse('2026-07-16T12:00:00Z')),
        );
    }

    public function testDateUtcCoercesUnixTimestampAsUtc(): void
    {
        self::assertSame('2026-07-16 12:00:00', $this->libraryAt('2026-07-16T12:00:00Z')->dateUtc(1_784_203_200));
    }

    public function testTimeIsoFormatsLocalTimeAsIso(): void
    {
        self::assertSame(
            '09:30:15',
            $this->libraryAt('2026-07-16T12:00:00Z')->timeIso(LocalTime::of(hour: 9, minute: 30, second: 15)),
        );
    }

    public function testTimeIsoExtractsTimeFromInstant(): void
    {
        self::assertSame(
            '12:00:00',
            $this->libraryAt('2026-07-16T12:00:00Z')->timeIso(Instant::parse('2026-07-16T12:00:00Z')),
        );
    }

    public function testTimeIsoCoercesStringInput(): void
    {
        self::assertSame(
            '12:00:00',
            $this->libraryAt('2026-07-16T12:00:00Z')->timeIso('2026-07-16T12:00:00Z'),
        );
    }
}
