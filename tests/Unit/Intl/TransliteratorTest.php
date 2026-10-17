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
use Tuxxedo\Intl\Transliteration;
use Tuxxedo\Intl\Transliterator;

class TransliteratorTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: Transliteration, 2: string}>
     */
    public static function providesTransliterationCases(): \Generator
    {
        yield 'ascii folds Latin letters' => [
            'Ærø Øst',
            Transliteration::ASCII,
            'AEro Ost',
        ];

        yield 'ascii expands sharp s' => [
            'straße',
            Transliteration::ASCII,
            'strasse',
        ];

        yield 'ascii romanizes Cyrillic' => [
            'Москва',
            Transliteration::ASCII,
            'Moskva',
        ];

        yield 'ascii leaves plain ASCII alone' => [
            'hello world',
            Transliteration::ASCII,
            'hello world',
        ];

        yield 'latin keeps Latin text as is' => [
            'Ærø Øst',
            Transliteration::LATIN,
            'Ærø Øst',
        ];

        yield 'latin romanizes Cyrillic' => [
            'Москва',
            Transliteration::LATIN,
            'Moskva',
        ];

        yield 'latin keeps accents from Greek' => [
            'Ελλάδα',
            Transliteration::LATIN,
            'Elláda',
        ];
    }

    #[DataProvider('providesTransliterationCases')]
    public function testTransliterate(
        string $value,
        Transliteration $transliteration,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new Transliterator())->transliterate(
                value: $value,
                transliteration: $transliteration,
            ),
        );
    }

    public function testDefaultsToAsciiAndReusesTransliterators(): void
    {
        $transliterator = new Transliterator();

        self::assertSame('Ellada', $transliterator->transliterate('Ελλάδα'));
        self::assertSame('Elláda', $transliterator->transliterate('Ελλάδα', Transliteration::LATIN));
        self::assertSame('Ellada', $transliterator->transliterate('Ελλάδα'));
    }
}
