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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslationException;

class LocaleTest extends TestCase
{
    public function testParseBuildsCanonicalTagFromLanguageOnly(): void
    {
        $locale = Locale::parse('en');

        self::assertSame('en', $locale->tag);
        self::assertSame('en', $locale->language);
        self::assertNull($locale->script);
        self::assertNull($locale->region);
    }

    public function testParseBuildsCanonicalTagFromLanguageAndRegion(): void
    {
        $locale = Locale::parse('en-US');

        self::assertSame('en-US', $locale->tag);
    }

    public function testParseBuildsCanonicalTagFromLanguageScriptRegion(): void
    {
        $locale = Locale::parse('zh-Hant-TW');

        self::assertSame('zh-Hant-TW', $locale->tag);
    }

    public function testParseAcceptsHyphenForm(): void
    {
        $locale = Locale::parse('en-US');

        self::assertSame('en-US', $locale->tag);
        self::assertSame('en', $locale->language);
        self::assertSame('US', $locale->region);
    }

    public function testParseAcceptsPosixUnderscoreForm(): void
    {
        $locale = Locale::parse('en_US');

        self::assertSame('en-US', $locale->tag);
    }

    public function testParseNormalisesLanguageCasing(): void
    {
        $locale = Locale::parse('EN-us');

        self::assertSame('en-US', $locale->tag);
    }

    public function testParseNormalisesScriptCasing(): void
    {
        $locale = Locale::parse('zh-hant-tw');

        self::assertSame('zh-Hant-TW', $locale->tag);
        self::assertSame('Hant', $locale->script);
    }

    public function testParseAcceptsLanguageOnly(): void
    {
        $locale = Locale::parse('da');

        self::assertSame('da', $locale->tag);
        self::assertSame('da', $locale->language);
        self::assertNull($locale->script);
        self::assertNull($locale->region);
    }

    public function testParseAcceptsThreeLetterLanguage(): void
    {
        $locale = Locale::parse('fil-PH');

        self::assertSame('fil-PH', $locale->tag);
        self::assertSame('fil', $locale->language);
    }

    public function testParseAcceptsNumericRegion(): void
    {
        $locale = Locale::parse('es-419');

        self::assertSame('es-419', $locale->tag);
        self::assertSame('419', $locale->region);
    }

    public function testParseTrimsWhitespace(): void
    {
        $locale = Locale::parse('  en-US  ');

        self::assertSame('en-US', $locale->tag);
    }

    public function testParseRejectsEmptyInput(): void
    {
        self::expectException(TranslationException::class);

        Locale::parse('');
    }

    public function testParseRejectsEmptyInputAfterTrim(): void
    {
        self::expectException(TranslationException::class);

        Locale::parse('   ');
    }

    public function testParseRejectsInvalidLanguage(): void
    {
        self::expectException(TranslationException::class);

        Locale::parse('e-US');
    }

    public function testParseRejectsStraySegment(): void
    {
        self::expectException(TranslationException::class);

        Locale::parse('en-US-extra-nonsense');
    }

    public function testParseRejectsEmptySubtagBetweenHyphens(): void
    {
        self::expectException(TranslationException::class);

        Locale::parse('en--US');
    }

    public function testFallbackChainYieldsSelfOnly(): void
    {
        $locale = Locale::parse('en');

        $chain = \iterator_to_array($locale->fallbackChain(), false);

        self::assertCount(1, $chain);
        self::assertSame('en', $chain[0]->tag);
    }

    public function testFallbackChainYieldsLanguageAfterRegion(): void
    {
        $locale = Locale::parse('en-US');

        $chain = \iterator_to_array($locale->fallbackChain(), false);

        self::assertCount(2, $chain);
        self::assertSame('en-US', $chain[0]->tag);
        self::assertSame('en', $chain[1]->tag);
    }

    public function testFallbackChainYieldsScriptAndLanguage(): void
    {
        $locale = Locale::parse('zh-Hant-TW');

        $chain = \iterator_to_array($locale->fallbackChain(), false);

        self::assertCount(3, $chain);
        self::assertSame('zh-Hant-TW', $chain[0]->tag);
        self::assertSame('zh-Hant', $chain[1]->tag);
        self::assertSame('zh', $chain[2]->tag);
    }

    public function testConstructorIsNotPublic(): void
    {
        self::assertFalse(
            (new \ReflectionMethod(Locale::class, '__construct'))->isPublic(),
        );
    }
}
