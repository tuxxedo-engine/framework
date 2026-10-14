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

namespace Unit\Temporal\Humanizer\Message;

use Fixture\Intl\InMemoryPhraseLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Intl\CatalogueInspector;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Loader\FlatFileLoader;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\MissingKeyMode;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\Message\TranslatorMessageFormatter;
use Tuxxedo\Temporal\Humanizer\RelativeDirection;
use Tuxxedo\Temporal\Humanizer\RelativeUnit;

class TranslatorMessageFormatterTest extends TestCase
{
    /**
     * @return iterable<string, array{int, RelativeUnit, RelativeDirection, string}>
     */
    public static function finnishRelativeMatrix(): iterable
    {
        yield 'one hour past' => [
            1,
            RelativeUnit::HOURS,
            RelativeDirection::PAST,
            '1 tunti sitten',
        ];

        yield 'three hours past' => [
            3,
            RelativeUnit::HOURS,
            RelativeDirection::PAST,
            '3 tuntia sitten',
        ];

        yield 'one hour future' => [
            1,
            RelativeUnit::HOURS,
            RelativeDirection::FUTURE,
            '1 tunnin päästä',
        ];

        yield 'three hours future' => [
            3,
            RelativeUnit::HOURS,
            RelativeDirection::FUTURE,
            '3 tunnin päästä',
        ];

        yield 'two days past' => [
            2,
            RelativeUnit::DAYS,
            RelativeDirection::PAST,
            '2 päivää sitten',
        ];

        yield 'two days future' => [
            2,
            RelativeUnit::DAYS,
            RelativeDirection::FUTURE,
            '2 päivän päästä',
        ];
    }

    #[DataProvider('finnishRelativeMatrix')]
    public function testRelativeMomentInflectsPerDirection(
        int $count,
        RelativeUnit $unit,
        RelativeDirection $direction,
        string $expected,
    ): void {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse('fi-FI'),
            missingKeyMode: MissingKeyMode::STRICT,
        );

        $formatter = new TranslatorMessageFormatter(
            translator: new Translator(
                config: $config,
                loader: new InMemoryPhraseLoader(
                    catalogues: [
                        'fi-FI' => [
                            'temporal' => [
                                'humanizer' => [
                                    'relative' => [
                                        'past' => [
                                            'hour' => [
                                                'one' => '{count} tunti sitten',
                                                'other' => '{count} tuntia sitten',
                                            ],
                                            'day' => [
                                                'one' => '{count} päivä sitten',
                                                'other' => '{count} päivää sitten',
                                            ],
                                        ],
                                        'future' => [
                                            'hour' => [
                                                'one' => '{count} tunnin päästä',
                                                'other' => '{count} tunnin päästä',
                                            ],
                                            'day' => [
                                                'one' => '{count} päivän päästä',
                                                'other' => '{count} päivän päästä',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ),
                resolver: new StaticLocaleResolver(
                    config: $config,
                ),
                pluralSelector: new PluralSelector(),
            ),
        );

        self::assertSame(
            $expected,
            $formatter->forRelativeMoment(
                count: $count,
                unit: $unit,
                direction: $direction,
            ),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function shippedLocales(): iterable
    {
        foreach (CatalogueInspector::AVAILABLE_DEFAULT_LOCALES as $locale) {
            yield $locale => [
                $locale,
            ];
        }
    }

    #[DataProvider('shippedLocales')]
    public function testShippedCatalogueMatchesEnglishKeysAndPlaceholders(
        string $locale,
    ): void {
        $english = CatalogueInspector::leaves('en-US', 'temporal');
        $translated = CatalogueInspector::leaves($locale, 'temporal');

        self::assertNotSame([], $translated);
        self::assertSame(\array_keys($english), \array_keys($translated));

        foreach ($english as $path => $value) {
            self::assertSame(
                CatalogueInspector::placeholders($value),
                CatalogueInspector::placeholders($translated[$path]),
                $path,
            );
        }
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function shippedPluralMatrix(): iterable
    {
        yield 'da-DK one' => ['da-DK', 1, '1 time'];
        yield 'da-DK other' => ['da-DK', 5, '5 timer'];
        yield 'fi-FI one' => ['fi-FI', 1, '1 tunti'];
        yield 'fi-FI other' => ['fi-FI', 5, '5 tuntia'];
        yield 'sv-SE one' => ['sv-SE', 1, '1 timme'];
        yield 'sv-SE other' => ['sv-SE', 5, '5 timmar'];
        yield 'nb-NO one' => ['nb-NO', 1, '1 time'];
        yield 'nb-NO other' => ['nb-NO', 5, '5 timer'];
        yield 'nn-NO one' => ['nn-NO', 1, '1 time'];
        yield 'nn-NO other' => ['nn-NO', 5, '5 timar'];
        yield 'is-IS one' => ['is-IS', 1, '1 klukkustund'];
        yield 'is-IS other' => ['is-IS', 5, '5 klukkustundir'];
        yield 'is-IS twenty-one' => ['is-IS', 21, '21 klukkustund'];
    }

    #[DataProvider('shippedPluralMatrix')]
    public function testShippedCataloguePluralisesDurationUnits(
        string $locale,
        int $count,
        string $expected,
    ): void {
        $config = new IntlConfig(
            defaultLocale: Locale::parse($locale),
            missingKeyMode: MissingKeyMode::STRICT,
        );
        $formatter = new TranslatorMessageFormatter(
            translator: new Translator(
                config: $config,
                loader: new FlatFileLoader(
                    rootDirectory: CatalogueInspector::RESOURCES_ROOT,
                ),
                resolver: new StaticLocaleResolver(
                    config: $config,
                ),
                pluralSelector: new PluralSelector(),
            ),
        );

        self::assertSame(
            $expected,
            $formatter->forDuration(
                parts: [
                    [
                        $count,
                        RelativeUnit::HOURS,
                    ],
                ],
                style: HumanizedStyle::LONG,
            ),
        );
    }
}
