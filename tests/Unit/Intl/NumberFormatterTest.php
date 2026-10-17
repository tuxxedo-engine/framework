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
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\NumberFormatter;

class NumberFormatterTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: int|float, 1: int, 2: string|null, 3: string}>
     */
    public static function providesFormatCases(): \Generator
    {
        yield 'en-US' => [
            1234567.891,
            2,
            'en-US',
            '1,234,567.89',
        ];

        yield 'da-DK' => [
            1234567.891,
            2,
            'da-DK',
            '1.234.567,89',
        ];

        yield 'cs-CZ' => [
            1234567.891,
            2,
            'cs-CZ',
            "1\u{00A0}234\u{00A0}567,89",
        ];

        yield 'integer without decimals' => [
            1234,
            0,
            'da-DK',
            '1.234',
        ];

        yield 'pads to the requested decimals' => [
            5,
            2,
            'en-US',
            '5.00',
        ];

        yield 'rounds half up' => [
            2.5,
            0,
            'en-US',
            '3',
        ];

        yield 'root without locale' => [
            1234567.891,
            2,
            null,
            '1,234,567.89',
        ];
    }

    #[DataProvider('providesFormatCases')]
    public function testFormatFollowsTheLocale(
        int|float $value,
        int $decimals,
        ?string $locale,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new NumberFormatter())->format(
                value: $value,
                decimals: $decimals,
                locale: $locale !== null
                    ? Locale::parse($locale)
                    : null,
            ),
        );
    }

    public function testFormatterIsReusedAcrossDecimalSettings(): void
    {
        $formatter = new NumberFormatter();
        $danish = Locale::parse('da-DK');

        self::assertSame('1.234,50', $formatter->format(1234.5, 2, $danish));
        self::assertSame('1.235', $formatter->format(1234.5, 0, $danish));
    }
}
