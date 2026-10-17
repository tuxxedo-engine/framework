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

namespace Unit\Intl;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\DateFormatter;
use Tuxxedo\Intl\DateStyle;
use Tuxxedo\Intl\Locale;

class DateFormatterTest extends TestCase
{
    private function thursdayAfternoon(
        string $timeZone = 'Europe/Copenhagen',
    ): \DateTimeImmutable {
        return (new \DateTimeImmutable('2026-10-15 12:30:00', new \DateTimeZone('UTC')))
            ->setTimezone(new \DateTimeZone($timeZone));
    }

    /**
     * @return \Generator<string, array{0: string, 1: DateStyle, 2: DateStyle, 3: string}>
     */
    public static function providesFormatCases(): \Generator
    {
        yield 'cs-CZ full date, short time' => [
            'cs-CZ',
            DateStyle::FULL,
            DateStyle::SHORT,
            'čtvrtek 15. října 2026 v 14:30',
        ];

        yield 'cs-CZ short date, short time' => [
            'cs-CZ',
            DateStyle::SHORT,
            DateStyle::SHORT,
            '15.10.26 14:30',
        ];

        yield 'da-DK full date only' => [
            'da-DK',
            DateStyle::FULL,
            DateStyle::NONE,
            'torsdag den 15. oktober 2026',
        ];

        yield 'da-DK long date only' => [
            'da-DK',
            DateStyle::LONG,
            DateStyle::NONE,
            '15. oktober 2026',
        ];

        yield 'da-DK medium date only' => [
            'da-DK',
            DateStyle::MEDIUM,
            DateStyle::NONE,
            '15. okt. 2026',
        ];

        yield 'da-DK short date only' => [
            'da-DK',
            DateStyle::SHORT,
            DateStyle::NONE,
            '15.10.2026',
        ];

        yield 'da-DK short time only' => [
            'da-DK',
            DateStyle::NONE,
            DateStyle::SHORT,
            '14.30',
        ];

        yield 'en-US full date only' => [
            'en-US',
            DateStyle::FULL,
            DateStyle::NONE,
            'Thursday, October 15, 2026',
        ];
    }

    #[DataProvider('providesFormatCases')]
    public function testFormatFollowsTheLocaleAndStyles(
        string $locale,
        DateStyle $dateStyle,
        DateStyle $timeStyle,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new DateFormatter())->format(
                value: $this->thursdayAfternoon(),
                locale: Locale::parse($locale),
                dateStyle: $dateStyle,
                timeStyle: $timeStyle,
            ),
        );
    }

    public function testFormatRendersInTheValuesOwnTimeZone(): void
    {
        $formatter = new DateFormatter();
        $danish = Locale::parse('da-DK');

        self::assertSame(
            '14.30',
            $formatter->format(
                value: $this->thursdayAfternoon('Europe/Copenhagen'),
                locale: $danish,
                dateStyle: DateStyle::NONE,
                timeStyle: DateStyle::SHORT,
            ),
        );

        self::assertSame(
            '12.30',
            $formatter->format(
                value: $this->thursdayAfternoon('UTC'),
                locale: $danish,
                dateStyle: DateStyle::NONE,
                timeStyle: DateStyle::SHORT,
            ),
        );
    }
}
