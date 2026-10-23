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

use Fixture\Intl\InMemoryPhraseLoader;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\View\Lumi\Library\Standard\IntlLibrary;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;

class IntlLibraryTest extends TestCase
{
    /**
     * @param array<string, array<string, array<string, mixed>|null>> $catalogues
     */
    private function makeLibrary(
        array $catalogues,
    ): IntlLibrary {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse('en-US'),
            supportedLocales: [
                Locale::parse('en-US'),
                Locale::parse('da-DK'),
            ],
        );

        return new IntlLibrary(
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

    /**
     * @param array<string, array<string, array<string, mixed>|null>> $catalogues
     */
    private function makeLibraryWithoutSupportedLocales(
        array $catalogues,
        string $defaultLocale = 'en-US',
    ): IntlLibrary {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse($defaultLocale),
        );

        return new IntlLibrary(
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
        $library = $this->makeLibrary(
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
            $library->translate(
                context: new StubRuntimeContext(),
                key: 'common.greeting',
            ),
        );
    }

    public function testTranslateInterpolatesReplacements(): void
    {
        $library = $this->makeLibrary(
            catalogues: [
                'en-US' => [
                    'common' => [
                        'greeting' => 'Hello {name}',
                    ],
                ],
            ],
        );

        self::assertSame(
            'Hello Kalle',
            $library->translate(
                context: new StubRuntimeContext(),
                key: 'common.greeting',
                replacements: [
                    'name' => 'Kalle',
                ],
            ),
        );
    }

    public function testTranslateHonoursExplicitLocale(): void
    {
        $library = $this->makeLibrary(
            catalogues: [
                'en-US' => [
                    'common' => [
                        'greeting' => 'Hello',
                    ],
                ],
                'da-DK' => [
                    'common' => [
                        'greeting' => 'Hej',
                    ],
                ],
            ],
        );

        self::assertSame(
            'Hej',
            $library->translate(
                context: new StubRuntimeContext(),
                key: 'common.greeting',
                locale: 'da-DK',
            ),
        );
    }

    public function testLocaleDirectiveOverridesTheTranslatorLocale(): void
    {
        $library = $this->makeLibrary(
            catalogues: [
                'en-US' => [
                    'common' => [
                        'greeting' => 'Hello',
                    ],
                ],
                'da-DK' => [
                    'common' => [
                        'greeting' => 'Hej',
                    ],
                ],
            ],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'da-DK',
            ],
        );

        self::assertSame('Hej', $library->translate($context, 'common.greeting'));
        self::assertSame('da-DK', $library->locale($context)->tag);
        self::assertSame('1.234,5', $library->localeNumber($context, 1234.5, 1));
        self::assertSame('15.10.2026', $library->localeDateShort($context, LocalDate::of(2026, 10, 15)));
    }

    public function testExplicitLocaleArgumentWinsOverTheLocaleDirective(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'da-DK',
            ],
        );

        self::assertSame('cs-CZ', $library->locale($context, 'cs-CZ')->tag);
    }

    public function testLocaleDateFollowsTheTimeZoneDirective(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.timezone' => 'Europe/Copenhagen',
            ],
        );

        self::assertSame('15.10.26 14:30', $library->localeDateShort($context, Instant::parse('2026-10-15T12:30:00Z'), 'cs-CZ'));
    }

    public function testNullLocaleDirectiveFallsBackToTheTranslatorLocale(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => null,
            ],
        );

        self::assertSame('en-US', $library->locale($context)->tag);
    }

    public function testPluralSelectsOneCategory(): void
    {
        $library = $this->makeLibrary(
            catalogues: [
                'en-US' => [
                    'cart' => [
                        'items' => [
                            'one' => '{count} item',
                            'other' => '{count} items',
                        ],
                    ],
                ],
            ],
        );

        self::assertSame(
            '1 item',
            $library->plural(
                context: new StubRuntimeContext(),
                key: 'cart.items',
                count: 1,
                replacements: [
                    'count' => 1,
                ],
            ),
        );
    }

    public function testPluralSelectsOtherCategory(): void
    {
        $library = $this->makeLibrary(
            catalogues: [
                'en-US' => [
                    'cart' => [
                        'items' => [
                            'one' => '{count} item',
                            'other' => '{count} items',
                        ],
                    ],
                ],
            ],
        );

        self::assertSame(
            '3 items',
            $library->plural(
                context: new StubRuntimeContext(),
                key: 'cart.items',
                count: 3,
                replacements: [
                    'count' => 3,
                ],
            ),
        );
    }

    public function testPluralHonoursExplicitLocale(): void
    {
        $library = $this->makeLibrary(
            catalogues: [
                'en-US' => [
                    'cart' => [
                        'items' => [
                            'one' => '{count} item',
                            'other' => '{count} items',
                        ],
                    ],
                ],
                'da-DK' => [
                    'cart' => [
                        'items' => [
                            'one' => '{count} vare',
                            'other' => '{count} varer',
                        ],
                    ],
                ],
            ],
        );

        self::assertSame(
            '3 varer',
            $library->plural(
                context: new StubRuntimeContext(),
                key: 'cart.items',
                count: 3,
                replacements: [
                    'count' => 3,
                ],
                locale: 'da-DK',
            ),
        );
    }

    public function testTIsRegisteredAsAliasOfTranslate(): void
    {
        $names = [];
        $metadata = CallableDiscoverer::discoverFunctions(IntlLibrary::class);

        foreach ($metadata as $entry) {
            $names[] = $entry->name;

            if ($entry->name === 'translate') {
                self::assertSame(
                    [
                        '_',
                        't',
                    ],
                    $entry->aliases,
                );
            }
        }

        self::assertContains('translate', $names);
        self::assertNotContains('t', $names);
    }

    public function testLocaleWithoutTagIsTheTranslatorLocale(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        self::assertSame('en-US', $library->locale(new StubRuntimeContext())->tag);
        self::assertTrue($library->localeIsLtr(new StubRuntimeContext()));
        self::assertFalse($library->localeIsRtl(new StubRuntimeContext()));
        self::assertSame('ltr', $library->localeDirection(new StubRuntimeContext()));
    }

    public function testLocaleWithTagParsesIt(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        self::assertSame('da-DK', $library->locale(new StubRuntimeContext(), 'da_dk')->tag);
        self::assertTrue($library->localeIsLtr(new StubRuntimeContext(), 'da-DK'));
        self::assertTrue($library->localeIsRtl(new StubRuntimeContext(), 'ar'));
        self::assertFalse($library->localeIsLtr(new StubRuntimeContext(), 'he-IL'));
        self::assertSame('rtl', $library->localeDirection(new StubRuntimeContext(), 'fa'));
        self::assertSame('ltr', $library->localeDirection(new StubRuntimeContext(), 'fi-FI'));
    }

    public function testLocaleFollowsTranslatorLocaleOverride(): void
    {
        $config = new IntlConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $library = new IntlLibrary(
            translator: (new Translator(
                config: $config,
                loader: new InMemoryPhraseLoader(
                    catalogues: [],
                ),
                resolver: new StaticLocaleResolver(
                    config: $config,
                ),
                pluralSelector: new PluralSelector(),
            ))->withLocale(Locale::parse('ar-EG')),
        );

        self::assertSame('ar-EG', $library->locale(new StubRuntimeContext())->tag);
        self::assertTrue($library->localeIsRtl(new StubRuntimeContext()));
        self::assertSame('rtl', $library->localeDirection(new StubRuntimeContext()));
    }

    public function testInvalidTagThrows(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        self::expectException(TranslationException::class);

        $library->localeIsRtl(new StubRuntimeContext(), 'not a locale');
    }

    public function testLocaleSortUsesTheTranslatorLocaleUnlessGiven(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        $names = [
            'Zoë',
            'Åse',
            'anna',
        ];

        self::assertSame(
            [
                2 => 'anna',
                1 => 'Åse',
                0 => 'Zoë',
            ],
            $library->localeSort(new StubRuntimeContext(), $names),
        );

        self::assertSame(
            [
                2 => 'anna',
                0 => 'Zoë',
                1 => 'Åse',
            ],
            $library->localeSort(new StubRuntimeContext(), $names, 'da-DK'),
        );
    }

    public function testLocaleKsortUsesTheTranslatorLocaleUnlessGiven(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        $values = [
            'Åse' => 1,
            'Zoë' => 2,
        ];

        self::assertSame(
            [
                'Åse' => 1,
                'Zoë' => 2,
            ],
            $library->localeKsort(new StubRuntimeContext(), $values),
        );

        self::assertSame(
            [
                'Zoë' => 2,
                'Åse' => 1,
            ],
            $library->localeKsort(new StubRuntimeContext(), $values, 'da-DK'),
        );
    }

    public function testLocaleNumberUsesTheTranslatorLocaleUnlessGiven(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        self::assertSame('1,234,567.89', $library->localeNumber(new StubRuntimeContext(), 1234567.891, 2));
        self::assertSame('1.234.567,89', $library->localeNumber(new StubRuntimeContext(), 1234567.891, 2, 'da-DK'));
        self::assertSame('1,235', $library->localeNumber(new StubRuntimeContext(), 1234.5));
    }

    public function testLocaleCurrencyUsesTheArgumentThenTheCurrencyDirective(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.currency' => 'EUR',
            ],
        );

        self::assertSame('€1,234.50', $library->localeCurrency($context, 1234.5));
        self::assertSame('$1,234.50', $library->localeCurrency($context, 1234.5, 'USD'));
        self::assertSame("€\u{00A0}1.234,50", $library->localeCurrency($context, 1234.5, null, 'pt-BR'));
    }

    public function testTimeZoneFunctionsFollowTheLocaleDirective(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'da-DK',
            ],
        );

        $listed = $library->timezoneList(
            $context,
            [
                'Europe/Lisbon',
                'Europe/Copenhagen',
            ],
        );

        self::assertSame('Lissabon', $listed[0]->city);
        self::assertSame('København', $listed[1]->city);

        $danish = $library->timezoneAll($context, 'DK');

        self::assertCount(1, $danish);
        self::assertSame('Danmark-tid', $danish[0]->displayName);

        $cities = [];

        foreach ($library->timezoneGroups($context) as $group) {
            $cities[] = $group->cities[0];
        }

        self::assertContains('København', $cities);
    }

    public function testLocaleCurrencyThrowsWithoutCurrency(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        try {
            $library->localeCurrency(new StubRuntimeContext(), 1234.5);

            self::fail('Expected TranslationException');
        } catch (TranslationException $exception) {
            self::assertStringContainsString(
                'tuxxedo.currency',
                $exception->getMessage(),
            );
        }
    }

    public function testLocaleDateUsesTheTranslatorLocaleUnlessGiven(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        $value = new \DateTimeImmutable('2026-10-15 14:30:00', new \DateTimeZone('Europe/Copenhagen'));

        self::assertSame('čtvrtek 15. října 2026 v 14:30', $library->localeDateFull(new StubRuntimeContext(), $value, 'cs-CZ'));
        self::assertSame('15.10.26 14:30', $library->localeDateShort(new StubRuntimeContext(), $value, 'cs-CZ'));
        self::assertSame('Thursday, October 15, 2026', $library->localeDateFull(new StubRuntimeContext(), LocalDate::of(2026, 10, 15)));
        self::assertSame('10/15/26', $library->localeDateShort(new StubRuntimeContext(), LocalDate::of(2026, 10, 15)));
        self::assertSame('09.05', $library->localeDateShort(new StubRuntimeContext(), LocalTime::of(9, 5), 'da-DK'));
        self::assertSame('15. oktober 2026', $library->localeDateLong(new StubRuntimeContext(), LocalDate::of(2026, 10, 15), 'da-DK'));
        self::assertSame('15. okt. 2026', $library->localeDateMedium(new StubRuntimeContext(), LocalDate::of(2026, 10, 15), 'da-DK'));
    }

    public function testAsciiAndRomanizeTransliterate(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        self::assertSame('AEro Ost', $library->ascii('Ærø Øst'));
        self::assertSame('Ellada', $library->ascii('Ελλάδα'));
        self::assertSame('Elláda', $library->romanize('Ελλάδα'));
    }

    public function testLocaleAllListsDefaultAndSupportedLocales(): void
    {
        $library = $this->makeLibrary(
            catalogues: [],
        );

        self::assertSame(
            [
                'da-DK',
                'en-US',
            ],
            \array_map(
                static fn (LocaleInterface $locale): string => $locale->tag,
                $library->localeAll(),
            ),
        );
    }

    public function testLocaleAllSortsByTheLatinSpellingOfTheOwnLanguageName(): void
    {
        $config = new IntlConfig(
            defaultLocale: Locale::parse('en-US'),
            supportedLocales: [
                Locale::parse('sv-SE'),
                Locale::parse('ru-RU'),
                Locale::parse('cs-CZ'),
                Locale::parse('el-GR'),
            ],
        );

        $library = new IntlLibrary(
            translator: new Translator(
                config: $config,
                loader: new InMemoryPhraseLoader(
                    catalogues: [],
                ),
                resolver: new StaticLocaleResolver(
                    config: $config,
                ),
                pluralSelector: new PluralSelector(),
            ),
        );

        self::assertSame(
            [
                'cs-CZ',
                'el-GR',
                'en-US',
                'ru-RU',
                'sv-SE',
            ],
            \array_map(
                static fn (LocaleInterface $locale): string => $locale->tag,
                $library->localeAll(),
            ),
        );
    }

    public function testTIsRegisteredAsFilterAliasOfTranslate(): void
    {
        $names = [];
        $metadata = CallableDiscoverer::discoverFilters(IntlLibrary::class);

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
        $library = $this->makeLibraryWithoutSupportedLocales(
            catalogues: [],
            defaultLocale: 'da-DK',
        );

        $instant = Instant::parse('2026-10-15T12:30:00+00:00');

        self::assertSame('torsdag den 15. oktober 2026 kl. 12.30', $library->localeDateFull(new StubRuntimeContext(), $instant));
        self::assertSame('15.10.2026', $library->localeDateShort(new StubRuntimeContext(), LocalDate::of(2026, 10, 15)));
        self::assertSame('14.30', $library->localeDateShort(new StubRuntimeContext(), LocalTime::of(14, 30)));
        self::assertSame('15. oktober 2026', $library->localeDateLong(new StubRuntimeContext(), LocalDate::of(2026, 10, 15)));
        self::assertSame('15. okt. 2026', $library->localeDateMedium(new StubRuntimeContext(), LocalDate::of(2026, 10, 15)));
    }

    public function testLocaleDateFollowsTheLocaleAndTimeZoneDirectives(): void
    {
        $library = $this->makeLibraryWithoutSupportedLocales(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'cs-CZ',
                'tuxxedo.timezone' => 'Europe/Copenhagen',
            ],
        );

        self::assertSame('15.10.26 14:30', $library->localeDateShort($context, Instant::parse('2026-10-15T12:30:00Z')));
    }

    public function testCurrencyFilterFollowsTheCurrencyAndLocaleDirectives(): void
    {
        $library = $this->makeLibraryWithoutSupportedLocales(
            catalogues: [],
        );

        self::assertSame(
            "234,50\u{00A0}€",
            $library->localeCurrency(
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
        $library = $this->makeLibraryWithoutSupportedLocales(
            catalogues: [],
        );

        $this->expectException(TranslationException::class);

        $library->localeCurrency(new StubRuntimeContext(), 1234.5);
    }
}
