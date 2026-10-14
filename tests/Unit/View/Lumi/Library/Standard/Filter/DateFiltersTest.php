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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Loader\FlatFileLoader;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\MissingKeyMode;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Temporal\Duration;
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
use Tuxxedo\View\Lumi\Library\Standard\Filter\DateFilters;

class DateFiltersTest extends TestCase
{
    private function filtersAt(
        string $reference,
    ): DateFilters {
        return new DateFilters(
            humanizer: new Humanizer(
                clock: new FixedClock(Instant::parse($reference)),
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

    public function testDateAgoHumanizesPastInstant(): void
    {
        $filters = $this->filtersAt('2026-07-16T12:00:00Z');
        $moment = Instant::parse('2026-07-16T12:00:00Z')->minus(Duration::fromHours(3));

        self::assertSame('3 hours ago', $filters->dateAgo($moment));
    }

    public function testDateAgoHumanizesFutureInstant(): void
    {
        $filters = $this->filtersAt('2026-07-16T12:00:00Z');
        $moment = Instant::parse('2026-07-16T12:00:00Z')->plus(Duration::fromMinutes(5));

        self::assertSame('in 5 minutes', $filters->dateAgo($moment));
    }

    public function testDateAgoHumanizesCoercedStringInput(): void
    {
        $filters = $this->filtersAt('2026-07-16T12:00:00Z');

        self::assertSame('1 hour ago', $filters->dateAgo('2026-07-16T11:00:00Z'));
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
        self::assertSame($expected, $this->filtersAt('2026-07-16T12:00:00Z')->dateIso($value));
    }

    public function testDateLongFormatsInstantWithLongPreset(): void
    {
        self::assertSame(
            'Thursday, July 16, 2026 12:00 PM',
            $this->filtersAt('2026-07-16T12:00:00Z')->dateLong(Instant::parse('2026-07-16T12:00:00Z')),
        );
    }

    public function testDateShortFormatsInstantWithShortPreset(): void
    {
        self::assertSame(
            '2026-07-16 12:00',
            $this->filtersAt('2026-07-16T12:00:00Z')->dateShort(Instant::parse('2026-07-16T12:00:00Z')),
        );
    }

    public function testDateShortCoercesStringInput(): void
    {
        self::assertSame(
            '2026-07-16 12:00',
            $this->filtersAt('2026-07-16T12:00:00Z')->dateShort('2026-07-16T12:00:00Z'),
        );
    }

    public function testDateUtcShiftsNonUtcInstantToUtc(): void
    {
        self::assertSame(
            '2026-07-16 11:00:00',
            $this->filtersAt('2026-07-16T12:00:00Z')->dateUtc(Instant::parse('2026-07-16T13:00:00+02:00')),
        );
    }

    public function testDateUtcHandlesAlreadyUtcInstant(): void
    {
        self::assertSame(
            '2026-07-16 12:00:00',
            $this->filtersAt('2026-07-16T12:00:00Z')->dateUtc(Instant::parse('2026-07-16T12:00:00Z')),
        );
    }

    public function testDateUtcCoercesUnixTimestampAsUtc(): void
    {
        self::assertSame('2026-07-16 12:00:00', $this->filtersAt('2026-07-16T12:00:00Z')->dateUtc(1_784_203_200));
    }

    public function testTimeIsoFormatsLocalTimeAsIso(): void
    {
        self::assertSame(
            '09:30:15',
            $this->filtersAt('2026-07-16T12:00:00Z')->timeIso(LocalTime::of(hour: 9, minute: 30, second: 15)),
        );
    }

    public function testTimeIsoExtractsTimeFromInstant(): void
    {
        self::assertSame(
            '12:00:00',
            $this->filtersAt('2026-07-16T12:00:00Z')->timeIso(Instant::parse('2026-07-16T12:00:00Z')),
        );
    }

    public function testTimeIsoCoercesStringInput(): void
    {
        self::assertSame(
            '12:00:00',
            $this->filtersAt('2026-07-16T12:00:00Z')->timeIso('2026-07-16T12:00:00Z'),
        );
    }
}
