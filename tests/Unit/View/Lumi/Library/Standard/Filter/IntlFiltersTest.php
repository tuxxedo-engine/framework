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

use Fixture\Intl\InMemoryPhraseLoader;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\View\Lumi\Library\Standard\Filter\IntlFilters;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;

class IntlFiltersTest extends TestCase
{
    /**
     * @param array<string, array<string, array<string, mixed>|null>> $catalogues
     */
    private function makeFilters(
        array $catalogues,
        string $defaultLocale = 'en-US',
    ): IntlFilters {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse($defaultLocale),
        );

        return new IntlFilters(
            translator: new Translator(
                config: $config,
                loader: new InMemoryPhraseLoader(
                    catalogues: $catalogues,
                ),
                resolver: new StaticLocaleResolver(
                    config: $config,
                ),
                pluralSelector: new PluralSelector(),
            ),
        );
    }

    public function testTranslatesPlainKey(): void
    {
        $filters = $this->makeFilters(
            catalogues: [
                'en-US' => [
                    'common' => [
                        'greeting' => 'Hello world',
                    ],
                ],
            ],
        );

        self::assertSame(
            'Hello world',
            $filters->translate(
                context: new StubRuntimeContext(),
                value: 'common.greeting',
            ),
        );
    }

    public function testTIsRegisteredAsAliasOfTranslate(): void
    {
        $names = [];
        $metadata = CallableDiscoverer::discoverFilters(IntlFilters::class);

        foreach ($metadata as $entry) {
            $names[] = $entry->name;

            if ($entry->name === 'translate') {
                self::assertSame(
                    [
                        't',
                    ],
                    $entry->aliases,
                );
            }
        }

        self::assertContains('translate', $names);
        self::assertNotContains('t', $names);
    }

    public function testLocaleDateFiltersUseTheTranslatorLocale(): void
    {
        $filters = $this->makeFilters(
            catalogues: [],
            defaultLocale: 'da-DK',
        );

        $instant = Instant::parse('2026-10-15T12:30:00+00:00');

        self::assertSame('torsdag den 15. oktober 2026 kl. 12.30', $filters->localeDateLong(new StubRuntimeContext(), $instant));
        self::assertSame('15.10.2026', $filters->localeDateShort(new StubRuntimeContext(), LocalDate::of(2026, 10, 15)));
        self::assertSame('14.30', $filters->localeDateShort(new StubRuntimeContext(), LocalTime::of(14, 30)));
    }

    public function testAsciiAndRomanizeTransliterate(): void
    {
        $filters = $this->makeFilters(
            catalogues: [],
        );

        self::assertSame('Moskva', $filters->ascii('Москва'));
        self::assertSame('Elláda', $filters->romanize('Ελλάδα'));
    }

    public function testLocaleDirectiveOverridesTheTranslatorLocale(): void
    {
        $filters = $this->makeFilters(
            catalogues: [
                'en-US' => [
                    'common' => [
                        'greeting' => 'Hello',
                    ],
                ],
                'pt-BR' => [
                    'common' => [
                        'greeting' => 'Olá',
                    ],
                ],
            ],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'pt-BR',
            ],
        );

        self::assertSame('Olá', $filters->translate($context, 'common.greeting'));
        self::assertSame('15/10/2026', $filters->localeDateShort($context, LocalDate::of(2026, 10, 15)));
    }

    public function testLocaleDateFollowsTheLocaleAndTimeZoneDirectives(): void
    {
        $filters = $this->makeFilters(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'cs-CZ',
                'tuxxedo.timezone' => 'Europe/Copenhagen',
            ],
        );

        self::assertSame('15.10.26 14:30', $filters->localeDateShort($context, Instant::parse('2026-10-15T12:30:00Z')));
    }

    public function testCurrencyFilterFollowsTheCurrencyAndLocaleDirectives(): void
    {
        $filters = $this->makeFilters(
            catalogues: [],
        );

        self::assertSame(
            "234,50\u{00A0}€",
            $filters->localeCurrency(
                new StubRuntimeContext(
                    directives: [
                        'tuxxedo.currency' => 'EUR',
                        'tuxxedo.locale' => 'pt-PT',
                    ],
                ),
                234.5,
            ),
        );
    }

    public function testCurrencyFilterThrowsWithoutCurrencyDirective(): void
    {
        $filters = $this->makeFilters(
            catalogues: [],
        );

        $this->expectException(TranslationException::class);

        $filters->localeCurrency(new StubRuntimeContext(), 1234.5);
    }
}
