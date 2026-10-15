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

use Fixture\Intl\CountingPhraseLoader;
use Fixture\Intl\InMemoryPhraseLoader;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\Loader\PhraseLoaderInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\MissingKeyMode;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\LocaleResolverInterface;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Intl\TranslatorInterface;

class TranslatorTest extends TestCase
{
    private function makeConfig(
        MissingKeyMode $missingKeyMode = MissingKeyMode::PASSTHROUGH,
        ?Locale $defaultLocale = null,
    ): IntlConfigInterface {
        return new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: $defaultLocale ?? Locale::parse('en-US'),
            missingKeyMode: $missingKeyMode,
        );
    }

    /**
     * @param array<string, array<string, array<string, mixed>|null>> $catalogues
     */
    private function stubLoader(
        array $catalogues,
    ): PhraseLoaderInterface {
        return new InMemoryPhraseLoader(
            catalogues: $catalogues,
        );
    }

    private function makeTranslator(
        IntlConfigInterface $config,
        PhraseLoaderInterface $loader,
        ?LocaleResolverInterface $resolver = null,
    ): Translator {
        return new Translator(
            config: $config,
            loader: $loader,
            resolver: $resolver ?? new StaticLocaleResolver(
                config: $config,
            ),
            pluralSelector: new PluralSelector(),
        );
    }

    public function testTranslatesSimpleKey(): void
    {
        $config = $this->makeConfig();

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello world',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hello world',
            $translator->translate(
                key: 'common.greeting',
            ),
        );
    }

    public function testTranslatesNestedKey(): void
    {
        $config = $this->makeConfig();
        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'error' => [
                                'mail' => [
                                    'noSubject' => 'No subject supplied',
                                ],
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'No subject supplied',
            $translator->translate(
                key: 'common.error.mail.noSubject',
            ),
        );
    }

    public function testInterpolatesPlaceholder(): void
    {
        $config = $this->makeConfig();
        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello {name}',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hello Kalle',
            $translator->translate(
                key: 'common.greeting',
                replacements: [
                    'name' => 'Kalle',
                ],
            ),
        );
    }

    public function testNullReplacementRendersAsEmptyString(): void
    {
        $config = $this->makeConfig();
        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello {name}!',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hello !',
            $translator->translate(
                key: 'common.greeting',
                replacements: [
                    'name' => null,
                ],
            ),
        );
    }

    public function testScalarReplacementsCastToString(): void
    {
        $config = $this->makeConfig();
        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'count' => 'There are {n} items ({active})',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'There are 5 items (1)',
            $translator->translate(
                key: 'common.count',
                replacements: [
                    'n' => 5,
                    'active' => true,
                ],
            ),
        );
    }

    public function testBackslashEscapesLiteralBrace(): void
    {
        $config = $this->makeConfig();
        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'formula' => 'Use \\{name\\} to refer to the placeholder',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Use {name} to refer to the placeholder',
            $translator->translate(
                key: 'common.formula',
                replacements: [
                    'name' => 'Kalle',
                ],
            ),
        );
    }

    public function testDoubleBackslashRendersSingleBackslash(): void
    {
        $config = $this->makeConfig();
        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'formula' => 'Path is C:\\\\Users',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Path is C:\\Users',
            $translator->translate(
                key: 'common.formula',
            ),
        );
    }

    public function testBackslashBeforeNonSpecialCharStaysLiteral(): void
    {
        $config = $this->makeConfig();
        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'line' => 'A\\nB',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'A\\nB',
            $translator->translate(
                key: 'common.line',
            ),
        );
    }

    public function testUnknownPlaceholderStaysLiteral(): void
    {
        $config = $this->makeConfig();
        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello {name}',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hello {name}',
            $translator->translate(
                key: 'common.greeting',
            ),
        );
    }

    public function testMissingKeyPassthroughReturnsKey(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::PASSTHROUGH,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [],
            ),
        );

        self::assertSame(
            'common.missing',
            $translator->translate(
                key: 'common.missing',
            ),
        );
    }

    public function testMissingKeyMarkerReturnsMarkerString(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::MARKER,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [],
            ),
        );

        self::assertSame(
            '[missing:common.missing]',
            $translator->translate(
                key: 'common.missing',
            ),
        );
    }

    public function testMissingKeyStrictThrows(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::STRICT,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [],
            ),
        );

        self::expectException(TranslationException::class);

        $translator->translate(
            key: 'common.missing',
        );
    }

    public function testFallbackChainResolvesParentLocale(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en' => [
                        'common' => [
                            'greeting' => 'Hello from English',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hello from English',
            $translator->translate(
                key: 'common.greeting',
            ),
        );
    }

    public function testExplicitLocaleOverridesResolver(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
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
            ),
        );

        self::assertSame(
            'Hej',
            $translator->translate(
                key: 'common.greeting',
                locale: Locale::parse('da-DK'),
            ),
        );
    }

    public function testWithLocaleForksTranslatorWithLockedLocale(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
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
            ),
        );

        $forked = $translator->withLocale(
            locale: Locale::parse('da-DK'),
        );

        self::assertSame(
            'Hej',
            $forked->translate(
                key: 'common.greeting',
            ),
        );

        self::assertSame(
            'Hello',
            $translator->translate(
                key: 'common.greeting',
            ),
        );
    }

    public function testPluralSelectsOneSibling(): void
    {
        $config = $this->makeConfig();

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'items' => [
                                'one' => '{count} item',
                                'other' => '{count} items',
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            '1 item',
            $translator->plural(
                key: 'common.items',
                count: 1,
                replacements: [
                    'count' => 1,
                ],
            ),
        );
    }

    public function testPluralSelectsOtherSibling(): void
    {
        $config = $this->makeConfig();

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'items' => [
                                'one' => '{count} item',
                                'other' => '{count} items',
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            '5 items',
            $translator->plural(
                key: 'common.items',
                count: 5,
                replacements: [
                    'count' => 5,
                ],
            ),
        );
    }

    public function testPluralMissingSiblingFallsThroughMissingBehaviour(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::PASSTHROUGH,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'items' => [
                                'one' => '{count} item',
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'common.items',
            $translator->plural(
                key: 'common.items',
                count: 5,
            ),
        );
    }

    public function testPluralMissingSiblingStrictThrows(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::STRICT,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'items' => [
                                'one' => '{count} item',
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::expectException(TranslationException::class);

        $translator->plural(
            key: 'common.items',
            count: 5,
        );
    }

    public function testPluralMissingSiblingMarkerReturnsMarker(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::MARKER,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'items' => [
                                'one' => '{count} item',
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            '[missing_plural:common.items:other]',
            $translator->plural(
                key: 'common.items',
                count: 5,
            ),
        );
    }

    public function testEmptyKeyFallsThroughMissingBehaviour(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::PASSTHROUGH,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [],
            ),
        );

        self::assertSame(
            '',
            $translator->translate(
                key: '',
            ),
        );
    }

    public function testKeyWithEmptySegmentFallsThroughMissingBehaviour(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::PASSTHROUGH,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'common..greeting',
            $translator->translate(
                key: 'common..greeting',
            ),
        );
    }

    public function testKeyPathWalkingPastLeafFallsThroughMissingBehaviour(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::PASSTHROUGH,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'common.greeting.deeper',
            $translator->translate(
                key: 'common.greeting.deeper',
            ),
        );
    }

    public function testMissingKeyFallsBackToDefaultLocale(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello, {name}',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hello, Kalle',
            $translator->translate(
                key: 'common.greeting',
                replacements: [
                    'name' => 'Kalle',
                ],
                locale: Locale::parse('da-DK'),
            ),
        );
    }

    public function testDefaultLocaleFallbackWalksDefaultSubtagChain(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en' => [
                        'common' => [
                            'greeting' => 'Hello from English',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hello from English',
            $translator->translate(
                key: 'common.greeting',
                locale: Locale::parse('da-DK'),
            ),
        );
    }

    public function testRequestedLocaleChainTakesPrecedenceOverDefaultLocale(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'da' => [
                        'common' => [
                            'greeting' => 'Hej',
                        ],
                    ],
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hej',
            $translator->translate(
                key: 'common.greeting',
                locale: Locale::parse('da-DK'),
            ),
        );
    }

    public function testWithLocaleFallsBackToDefaultLocale(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'Hello',
            $translator->withLocale(Locale::parse('fi-FI'))->translate(
                key: 'common.greeting',
            ),
        );
    }

    public function testPluralFallbackToDefaultLocaleSelectsCategoryForDefaultLocale(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'items' => [
                                'one' => '{count} item',
                                'other' => '{count} items',
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            '5 items',
            $translator->plural(
                key: 'common.items',
                count: 5,
                replacements: [
                    'count' => 5,
                ],
                locale: Locale::parse('ru-RU'),
            ),
        );
    }

    public function testPluralPrefersRequestedLocaleOverDefaultLocale(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'ru' => [
                        'common' => [
                            'items' => [
                                'one' => '{count} предмет',
                                'few' => '{count} предмета',
                                'many' => '{count} предметов',
                                'other' => '{count} предмета',
                            ],
                        ],
                    ],
                    'en-US' => [
                        'common' => [
                            'items' => [
                                'one' => '{count} item',
                                'other' => '{count} items',
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            '5 предметов',
            $translator->plural(
                key: 'common.items',
                count: 5,
                replacements: [
                    'count' => 5,
                ],
                locale: Locale::parse('ru-RU'),
            ),
        );
    }

    public function testMissingKeyStrictReportsExplicitLocale(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::STRICT,
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [],
            ),
        );

        self::expectException(TranslationException::class);
        self::expectExceptionMessage('No translation found for key "common.missing" in locale "da-DK"');

        $translator->translate(
            key: 'common.missing',
            locale: Locale::parse('da-DK'),
        );
    }

    public function testMissingKeyStrictReportsLockedLocale(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::STRICT,
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [],
            ),
        );

        self::expectException(TranslationException::class);
        self::expectExceptionMessage('No translation found for key "common.missing" in locale "sv-SE"');

        $translator->withLocale(Locale::parse('sv-SE'))->translate(
            key: 'common.missing',
        );
    }

    public function testHasReportsResolvableKeys(): void
    {
        $config = $this->makeConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello',
                            'items' => [
                                'one' => '{count} item',
                                'other' => '{count} items',
                            ],
                        ],
                    ],
                    'da-DK' => [
                        'common' => [
                            'farewell' => 'Farvel',
                        ],
                    ],
                ],
            ),
        );

        self::assertTrue($translator->has('common.greeting'));
        self::assertTrue($translator->has('common.items.one'));
        self::assertFalse($translator->has('common.items'));
        self::assertFalse($translator->has('common.missing'));
        self::assertFalse($translator->has(''));
        self::assertFalse($translator->has('common..greeting'));
        self::assertFalse($translator->has('common.farewell'));
        self::assertTrue($translator->has('common.farewell', Locale::parse('da-DK')));
        self::assertTrue($translator->has('common.greeting', Locale::parse('da-DK')));
    }

    public function testHasIgnoresMissingKeyMode(): void
    {
        $config = $this->makeConfig(
            missingKeyMode: MissingKeyMode::STRICT,
        );

        $translator = $this->makeTranslator(
            config: $config,
            loader: $this->stubLoader(
                catalogues: [],
            ),
        );

        self::assertFalse($translator->has('common.missing'));
    }

    public function testLoadsEachCatalogueOnceAcrossLookupsAndForks(): void
    {
        $loader = new CountingPhraseLoader(
            loader: $this->stubLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello',
                            'farewell' => 'Goodbye',
                        ],
                    ],
                ],
            ),
        );

        $translator = $this->makeTranslator(
            config: $this->makeConfig(),
            loader: $loader,
        );

        $fork = $translator->withLocale(Locale::parse('en-US'));

        self::assertSame('Hello', $translator->translate('common.greeting'));
        self::assertSame('Goodbye', $translator->translate('common.farewell'));
        self::assertSame('Hello', $fork->translate('common.greeting'));
        self::assertTrue($fork->has('common.farewell'));

        self::assertSame(1, $loader->calls);
    }

    public function testContainerResolvesTranslatorWithoutIntlConfiguration(): void
    {
        self::assertSame(
            'Value at "email" is not a valid email address',
            (new Container())->resolve(TranslatorInterface::class)->translate(
                key: 'validator.email.invalid-format',
                replacements: [
                    'path' => 'email',
                ],
            ),
        );
    }

    public function testLocaleIsTheResolverLocaleUnlessOverridden(): void
    {
        $translator = $this->makeTranslator(
            config: $this->makeConfig(
                defaultLocale: Locale::parse('da-DK'),
            ),
            loader: $this->stubLoader(
                catalogues: [],
            ),
        );

        self::assertSame('da-DK', $translator->locale->tag);
        self::assertSame('sv-SE', $translator->withLocale(Locale::parse('sv-SE'))->locale->tag);
        self::assertSame('da-DK', $translator->locale->tag);
    }
}
