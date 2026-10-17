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
use Tuxxedo\Intl\TranslationException;

class NumberFormatterTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: int|float, 1: int, 2: string, 3: string}>
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
    }

    #[DataProvider('providesFormatCases')]
    public function testFormatFollowsTheLocale(
        int|float $value,
        int $decimals,
        string $locale,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new NumberFormatter())->format(
                value: $value,
                locale: Locale::parse($locale),
                decimals: $decimals,
            ),
        );
    }

    public function testFormatterIsReusedAcrossDecimalSettings(): void
    {
        $formatter = new NumberFormatter();
        $danish = Locale::parse('da-DK');

        self::assertSame('1.234,50', $formatter->format(1234.5, $danish, 2));
        self::assertSame('1.235', $formatter->format(1234.5, $danish, 0));
    }

    /**
     * @return \Generator<string, array{0: int|float, 1: string, 2: string, 3: string}>
     */
    public static function providesCurrencyCases(): \Generator
    {
        yield 'en-US dollars' => [
            1234.5,
            'USD',
            'en-US',
            '$1,234.50',
        ];

        yield 'en-US euros' => [
            1234.5,
            'EUR',
            'en-US',
            '€1,234.50',
        ];

        yield 'pt-PT euros' => [
            234.5,
            'EUR',
            'pt-PT',
            "234,50\u{00A0}€",
        ];

        yield 'pt-BR euros' => [
            1234.5,
            'EUR',
            'pt-BR',
            "€\u{00A0}1.234,50",
        ];

        yield 'pt-BR reais' => [
            1234.5,
            'BRL',
            'pt-BR',
            "R$\u{00A0}1.234,50",
        ];

        yield 'da-DK kroner' => [
            1234.5,
            'DKK',
            'da-DK',
            "1.234,50\u{00A0}kr.",
        ];

        yield 'yen has no minor unit and rounds half up' => [
            1234.5,
            'JPY',
            'ja-JP',
            '￥1,235',
        ];

        yield 'negative amount' => [
            -5,
            'USD',
            'en-US',
            '-$5.00',
        ];

        yield 'lower-case code is normalised' => [
            1234.5,
            'eur',
            'en-US',
            '€1,234.50',
        ];
    }

    #[DataProvider('providesCurrencyCases')]
    public function testFormatCurrencyFollowsTheLocaleAndTheCurrency(
        int|float $amount,
        string $currency,
        string $locale,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new NumberFormatter())->formatCurrency(
                amount: $amount,
                currency: $currency,
                locale: Locale::parse($locale),
            ),
        );
    }

    public function testFormatCurrencyDoesNotAffectDecimalFormatting(): void
    {
        $formatter = new NumberFormatter();
        $danish = Locale::parse('da-DK');

        self::assertSame("1.234,50\u{00A0}kr.", $formatter->formatCurrency(1234.5, 'DKK', $danish));
        self::assertSame('1.235', $formatter->format(1234.5, $danish));
    }

    public function testFormatCurrencyRejectsMalformedCode(): void
    {
        try {
            (new NumberFormatter())->formatCurrency(1, 'EURO', Locale::parse('en-US'));

            self::fail('Expected TranslationException');
        } catch (TranslationException $exception) {
            self::assertStringContainsString(
                'three-letter ISO 4217 code',
                $exception->getMessage(),
            );
        }
    }

    public function testLocaleWithoutIcuDataThrowsTranslationException(): void
    {
        self::expectException(TranslationException::class);

        (new NumberFormatter())->format(1, Locale::parse('und'));
    }
}
