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

namespace Unit\View\Lumi\Library\Standard\Function;

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
use Tuxxedo\View\Lumi\Library\Standard\Function\IntlFunctions;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;

class IntlFunctionsTest extends TestCase
{
    /**
     * @param array<string, array<string, array<string, mixed>|null>> $catalogues
     */
    private function makeFunctions(
        array $catalogues,
    ): IntlFunctions {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse('en-US'),
            supportedLocales: [
                Locale::parse('en-US'),
                Locale::parse('da-DK'),
            ],
        );

        return new IntlFunctions(
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
        $functions = $this->makeFunctions(
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
            $functions->translate(
                context: new StubRuntimeContext(),
                key: 'common.greeting',
            ),
        );
    }

    public function testTranslateInterpolatesReplacements(): void
    {
        $functions = $this->makeFunctions(
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
            $functions->translate(
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
        $functions = $this->makeFunctions(
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
            $functions->translate(
                context: new StubRuntimeContext(),
                key: 'common.greeting',
                locale: 'da-DK',
            ),
        );
    }

    public function testLocaleDirectiveOverridesTheTranslatorLocale(): void
    {
        $functions = $this->makeFunctions(
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

        self::assertSame('Hej', $functions->translate($context, 'common.greeting'));
        self::assertSame('da-DK', $functions->locale($context)->tag);
        self::assertSame('1.234,5', $functions->localeNumber($context, 1234.5, 1));
        self::assertSame('15.10.2026', $functions->localeDateShort($context, LocalDate::of(2026, 10, 15)));
    }

    public function testExplicitLocaleArgumentWinsOverTheLocaleDirective(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => 'da-DK',
            ],
        );

        self::assertSame('cs-CZ', $functions->locale($context, 'cs-CZ')->tag);
    }

    public function testLocaleDateFollowsTheTimeZoneDirective(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.timezone' => 'Europe/Copenhagen',
            ],
        );

        self::assertSame('15.10.26 14:30', $functions->localeDateShort($context, Instant::parse('2026-10-15T12:30:00Z'), 'cs-CZ'));
    }

    public function testNullLocaleDirectiveFallsBackToTheTranslatorLocale(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        $context = new StubRuntimeContext(
            directives: [
                'tuxxedo.locale' => null,
            ],
        );

        self::assertSame('en-US', $functions->locale($context)->tag);
    }

    public function testPluralSelectsOneCategory(): void
    {
        $functions = $this->makeFunctions(
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
            $functions->plural(
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
        $functions = $this->makeFunctions(
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
            $functions->plural(
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
        $functions = $this->makeFunctions(
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
            $functions->plural(
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
        $metadata = CallableDiscoverer::discoverFunctions(IntlFunctions::class);

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
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        self::assertSame('en-US', $functions->locale(new StubRuntimeContext())->tag);
        self::assertTrue($functions->localeIsLtr(new StubRuntimeContext()));
        self::assertFalse($functions->localeIsRtl(new StubRuntimeContext()));
        self::assertSame('ltr', $functions->localeDirection(new StubRuntimeContext()));
    }

    public function testLocaleWithTagParsesIt(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        self::assertSame('da-DK', $functions->locale(new StubRuntimeContext(), 'da_dk')->tag);
        self::assertTrue($functions->localeIsLtr(new StubRuntimeContext(), 'da-DK'));
        self::assertTrue($functions->localeIsRtl(new StubRuntimeContext(), 'ar'));
        self::assertFalse($functions->localeIsLtr(new StubRuntimeContext(), 'he-IL'));
        self::assertSame('rtl', $functions->localeDirection(new StubRuntimeContext(), 'fa'));
        self::assertSame('ltr', $functions->localeDirection(new StubRuntimeContext(), 'fi-FI'));
    }

    public function testLocaleFollowsTranslatorLocaleOverride(): void
    {
        $config = new IntlConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $functions = new IntlFunctions(
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

        self::assertSame('ar-EG', $functions->locale(new StubRuntimeContext())->tag);
        self::assertTrue($functions->localeIsRtl(new StubRuntimeContext()));
        self::assertSame('rtl', $functions->localeDirection(new StubRuntimeContext()));
    }

    public function testInvalidTagThrows(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        self::expectException(TranslationException::class);

        $functions->localeIsRtl(new StubRuntimeContext(), 'not a locale');
    }

    public function testLocaleSortUsesTheTranslatorLocaleUnlessGiven(): void
    {
        $functions = $this->makeFunctions(
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
            $functions->localeSort(new StubRuntimeContext(), $names),
        );

        self::assertSame(
            [
                2 => 'anna',
                0 => 'Zoë',
                1 => 'Åse',
            ],
            $functions->localeSort(new StubRuntimeContext(), $names, 'da-DK'),
        );
    }

    public function testLocaleKsortUsesTheTranslatorLocaleUnlessGiven(): void
    {
        $functions = $this->makeFunctions(
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
            $functions->localeKsort(new StubRuntimeContext(), $values),
        );

        self::assertSame(
            [
                'Zoë' => 2,
                'Åse' => 1,
            ],
            $functions->localeKsort(new StubRuntimeContext(), $values, 'da-DK'),
        );
    }

    public function testLocaleNumberUsesTheTranslatorLocaleUnlessGiven(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        self::assertSame('1,234,567.89', $functions->localeNumber(new StubRuntimeContext(), 1234567.891, 2));
        self::assertSame('1.234.567,89', $functions->localeNumber(new StubRuntimeContext(), 1234567.891, 2, 'da-DK'));
        self::assertSame('1,235', $functions->localeNumber(new StubRuntimeContext(), 1234.5));
    }

    public function testLocaleDateUsesTheTranslatorLocaleUnlessGiven(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        $value = new \DateTimeImmutable('2026-10-15 14:30:00', new \DateTimeZone('Europe/Copenhagen'));

        self::assertSame('čtvrtek 15. října 2026 v 14:30', $functions->localeDateLong(new StubRuntimeContext(), $value, 'cs-CZ'));
        self::assertSame('15.10.26 14:30', $functions->localeDateShort(new StubRuntimeContext(), $value, 'cs-CZ'));
        self::assertSame('Thursday, October 15, 2026', $functions->localeDateLong(new StubRuntimeContext(), LocalDate::of(2026, 10, 15)));
        self::assertSame('10/15/26', $functions->localeDateShort(new StubRuntimeContext(), LocalDate::of(2026, 10, 15)));
        self::assertSame('09.05', $functions->localeDateShort(new StubRuntimeContext(), LocalTime::of(9, 5), 'da-DK'));
    }

    public function testAsciiAndRomanizeTransliterate(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        self::assertSame('AEro Ost', $functions->ascii('Ærø Øst'));
        self::assertSame('Ellada', $functions->ascii('Ελλάδα'));
        self::assertSame('Elláda', $functions->romanize('Ελλάδα'));
    }

    public function testLocaleAllListsDefaultAndSupportedLocales(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        self::assertSame(
            [
                'da-DK',
                'en-US',
            ],
            \array_map(
                static fn (LocaleInterface $locale): string => $locale->tag,
                $functions->localeAll(),
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

        $functions = new IntlFunctions(
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
                $functions->localeAll(),
            ),
        );
    }
}
