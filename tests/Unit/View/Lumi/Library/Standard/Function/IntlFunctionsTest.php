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
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Intl\Translator;
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
                key: 'common.greeting',
                locale: 'da-DK',
            ),
        );
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

        self::assertSame('en-US', $functions->locale()->tag);
        self::assertTrue($functions->localeIsLtr());
        self::assertFalse($functions->localeIsRtl());
        self::assertSame('ltr', $functions->localeDirection());
    }

    public function testLocaleWithTagParsesIt(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        self::assertSame('da-DK', $functions->locale('da_dk')->tag);
        self::assertTrue($functions->localeIsLtr('da-DK'));
        self::assertTrue($functions->localeIsRtl('ar'));
        self::assertFalse($functions->localeIsLtr('he-IL'));
        self::assertSame('rtl', $functions->localeDirection('fa'));
        self::assertSame('ltr', $functions->localeDirection('fi-FI'));
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

        self::assertSame('ar-EG', $functions->locale()->tag);
        self::assertTrue($functions->localeIsRtl());
        self::assertSame('rtl', $functions->localeDirection());
    }

    public function testInvalidTagThrows(): void
    {
        $functions = $this->makeFunctions(
            catalogues: [],
        );

        self::expectException(TranslationException::class);

        $functions->localeIsRtl('not a locale');
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
