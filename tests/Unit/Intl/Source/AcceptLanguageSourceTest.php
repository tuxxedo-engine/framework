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

namespace Unit\Intl\Source;

use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\Source\AcceptLanguageSource;

class AcceptLanguageSourceTest extends TestCase
{
    /**
     * @param list<string> $preferredLanguages
     */
    private function requestWithPreferredLanguages(
        array $preferredLanguages,
    ): Request {
        $headers = new StubHeaderContext();
        $headers->preferredLanguages = $preferredLanguages;

        return new Request(
            headers: $headers,
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
        );
    }

    /**
     * @param list<string> $preferredLanguages
     * @param list<string> $supportedLocales
     */
    private function makeSource(
        array $preferredLanguages,
        array $supportedLocales = [],
        string $defaultLocale = 'en-US',
    ): AcceptLanguageSource {
        return new AcceptLanguageSource(
            request: $this->requestWithPreferredLanguages(
                preferredLanguages: $preferredLanguages,
            ),
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse($defaultLocale),
                supportedLocales: \array_map(Locale::parse(...), $supportedLocales),
            ),
        );
    }

    public function testReturnsNullWhenPreferredLanguagesEmpty(): void
    {
        $source = $this->makeSource(
            preferredLanguages: [],
        );

        self::assertNull($source->detect());
    }

    public function testPicksFirstParseableLanguage(): void
    {
        $source = $this->makeSource(
            preferredLanguages: [
                'da-DK',
                'en-US',
            ],
        );

        $detected = $source->detect();

        self::assertNotNull($detected);
        self::assertSame('da-DK', $detected->tag);
    }

    public function testSkipsUnparseableLanguageAndFallsToNext(): void
    {
        $source = $this->makeSource(
            preferredLanguages: [
                '!!garbage!!',
                'en-US',
            ],
        );

        $detected = $source->detect();

        self::assertNotNull($detected);
        self::assertSame('en-US', $detected->tag);
    }

    public function testReturnsNullWhenAllCandidatesUnparseable(): void
    {
        $source = $this->makeSource(
            preferredLanguages: [
                '!!garbage!!',
                '???',
            ],
        );

        self::assertNull($source->detect());
    }

    public function testPicksHighestRankedSupportedLanguage(): void
    {
        $detected = $this->makeSource(
            preferredLanguages: [
                'fr-FR',
                'da-DK',
                'sv-SE',
            ],
            supportedLocales: [
                'sv-SE',
                'da-DK',
            ],
        )->detect();

        self::assertNotNull($detected);
        self::assertSame('da-DK', $detected->tag);
    }

    public function testLanguageOnlyPreferenceMatchesRegionalSupportedLocale(): void
    {
        $detected = $this->makeSource(
            preferredLanguages: [
                'da',
            ],
            supportedLocales: [
                'da-DK',
            ],
        )->detect();

        self::assertNotNull($detected);
        self::assertSame('da-DK', $detected->tag);
    }

    public function testRegionalPreferenceMatchesLanguageOnlySupportedLocale(): void
    {
        $detected = $this->makeSource(
            preferredLanguages: [
                'fi-FI',
            ],
            supportedLocales: [
                'fi',
            ],
        )->detect();

        self::assertNotNull($detected);
        self::assertSame('fi', $detected->tag);
    }

    public function testExactMatchWinsOverBroaderMatch(): void
    {
        $detected = $this->makeSource(
            preferredLanguages: [
                'pt-BR',
            ],
            supportedLocales: [
                'pt',
                'pt-BR',
            ],
        )->detect();

        self::assertNotNull($detected);
        self::assertSame('pt-BR', $detected->tag);
    }

    public function testDefaultLocaleCountsAsSupported(): void
    {
        $detected = $this->makeSource(
            preferredLanguages: [
                'fr-FR',
                'en',
            ],
            supportedLocales: [
                'da-DK',
            ],
            defaultLocale: 'en-US',
        )->detect();

        self::assertNotNull($detected);
        self::assertSame('en-US', $detected->tag);
    }

    public function testReturnsNullWhenNoPreferenceIsSupported(): void
    {
        self::assertNull(
            $this->makeSource(
                preferredLanguages: [
                    'fr-FR',
                    'de',
                ],
                supportedLocales: [
                    'da-DK',
                ],
            )->detect(),
        );
    }

    public function testRegionalVariantsOfSameLanguageDoNotCrossMatch(): void
    {
        self::assertNull(
            $this->makeSource(
                preferredLanguages: [
                    'en-GB',
                ],
                supportedLocales: [
                    'da-DK',
                ],
                defaultLocale: 'en-US',
            )->detect(),
        );
    }

    public function testDetectsOncePerSourceInstance(): void
    {
        $request = $this->requestWithPreferredLanguages(
            preferredLanguages: [
                'da-DK',
            ],
        );

        $source = new AcceptLanguageSource(
            request: $request,
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
            ),
        );

        $first = $source->detect();

        self::assertInstanceOf(StubHeaderContext::class, $request->headers);
        $request->headers->preferredLanguages = [
            'fi-FI',
        ];

        $second = $source->detect();

        self::assertNotNull($first);
        self::assertSame($first, $second);
        self::assertSame('da-DK', $first->tag);
    }
}
