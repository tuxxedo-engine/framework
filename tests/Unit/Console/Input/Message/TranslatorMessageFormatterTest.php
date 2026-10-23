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

namespace Unit\Console\Input\Message;

use Fixture\Intl\InMemoryPhraseLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Intl\CatalogueInspector;
use Tuxxedo\Console\Input\Message\MessageFormatterInterface;
use Tuxxedo\Console\Input\Message\TranslatorMessageFormatter;
use Tuxxedo\Container\Container;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Loader\FlatFileLoader;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\MissingKeyMode;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Intl\TranslatorInterface;

class TranslatorMessageFormatterTest extends TestCase
{
    private function makeFormatter(
        TranslatorInterface $translator,
    ): TranslatorMessageFormatter {
        return new TranslatorMessageFormatter(
            translator: $translator,
        );
    }

    private function makeFrameworkTranslator(): TranslatorInterface
    {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse('en-US'),
            missingKeyMode: MissingKeyMode::PASSTHROUGH,
        );

        return new Translator(
            config: $config,
            loader: new FlatFileLoader(
                rootDirectory: \dirname(__DIR__, 5) . '/resources/intl',
            ),
            resolver: new StaticLocaleResolver(
                config: $config,
            ),
            pluralSelector: new PluralSelector(),
        );
    }

    private function makeLocaleTranslator(
        string $locale,
    ): TranslatorInterface {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse($locale),
            missingKeyMode: MissingKeyMode::STRICT,
        );

        return new Translator(
            config: $config,
            loader: new FlatFileLoader(
                rootDirectory: \dirname(__DIR__, 5) . '/resources/intl',
            ),
            resolver: new StaticLocaleResolver(
                config: $config,
            ),
            pluralSelector: new PluralSelector(),
        );
    }

    public function testConfirmIndicatorYesDefault(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertSame(
            '[Y/n]',
            $formatter->forConfirmIndicator(
                default: true,
            ),
        );
    }

    public function testConfirmIndicatorNoDefault(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertSame(
            '[y/N]',
            $formatter->forConfirmIndicator(
                default: false,
            ),
        );
    }

    public function testParseConfirmAnswerAcceptsYesVariants(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertTrue(
            $formatter->parseConfirmAnswer(
                answer: 'y',
            ),
        );

        self::assertTrue(
            $formatter->parseConfirmAnswer(
                answer: 'yes',
            ),
        );
    }

    public function testParseConfirmAnswerAcceptsNoVariants(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertFalse(
            $formatter->parseConfirmAnswer(
                answer: 'n',
            ),
        );

        self::assertFalse(
            $formatter->parseConfirmAnswer(
                answer: 'no',
            ),
        );
    }

    public function testParseConfirmAnswerReturnsNullForUnrecognised(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertNull(
            $formatter->parseConfirmAnswer(
                answer: 'maybe',
            ),
        );
    }

    public function testDefaultSuffixInterpolatesDefault(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertSame(
            ' [default: foo]',
            $formatter->forDefaultSuffix(
                default: 'foo',
            ),
        );
    }

    public function testInvalidChoiceMessage(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertSame(
            'Invalid choice, please try again.',
            $formatter->forInvalidChoice(),
        );
    }

    public function testEmptyAnswerNotAllowedMessage(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertSame(
            'A value is required.',
            $formatter->forEmptyAnswerNotAllowed(),
        );
    }

    public function testEofOnRequiredInputMessage(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeFrameworkTranslator(),
        );

        self::assertSame(
            'Reached end of input before a required value was provided.',
            $formatter->forEofOnRequiredInput(),
        );
    }

    public function testConfirmAnswerIsUnrecognisedWhenAnswerListsAreMissing(): void
    {
        foreach ([MissingKeyMode::PASSTHROUGH, MissingKeyMode::MARKER] as $mode) {
            $config = new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
                missingKeyMode: $mode,
            );

            $formatter = $this->makeFormatter(
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

            self::assertNull($formatter->parseConfirmAnswer('console.input.confirm.yesanswers'));
            self::assertNull($formatter->parseConfirmAnswer('console.input.confirm.noanswers'));
            self::assertNull($formatter->parseConfirmAnswer('[missing:console.input.confirm.yesanswers]'));
            self::assertNull($formatter->parseConfirmAnswer('y'));
        }
    }

    public function testConfirmIndicatorIsDerivedFromLocaleAnswers(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeLocaleTranslator('fi-FI'),
        );

        self::assertSame('[K/e]', $formatter->forConfirmIndicator(default: true));
        self::assertSame('[k/E]', $formatter->forConfirmIndicator(default: false));
    }

    public function testConfirmAnswersAreTheLocaleAnswersOnly(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeLocaleTranslator('fi-FI'),
        );

        self::assertSame(
            [
                'yes' => [
                    'k',
                    'kyllä',
                ],
                'no' => [
                    'e',
                    'ei',
                ],
            ],
            $formatter->confirmAnswers(),
        );
    }

    public function testConfirmAnswersForEnglishAreNotDuplicated(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeLocaleTranslator('en-US'),
        );

        self::assertSame(
            [
                'yes' => [
                    'y',
                    'yes',
                ],
                'no' => [
                    'n',
                    'no',
                ],
            ],
            $formatter->confirmAnswers(),
        );
    }

    public function testParseConfirmAnswerFoldsNonAsciiCase(): void
    {
        $finnish = $this->makeFormatter(
            translator: $this->makeLocaleTranslator('fi-FI'),
        );

        $icelandic = $this->makeFormatter(
            translator: $this->makeLocaleTranslator('is-IS'),
        );

        self::assertTrue($finnish->parseConfirmAnswer(answer: 'KYLLÄ'));
        self::assertFalse($finnish->parseConfirmAnswer(answer: ' Ei '));
        self::assertTrue($icelandic->parseConfirmAnswer(answer: 'JÁ'));
        self::assertFalse($icelandic->parseConfirmAnswer(answer: 'NEI'));
    }

    public function testParseConfirmAnswerRejectsEnglishUnderOtherLocales(): void
    {
        $formatter = $this->makeFormatter(
            translator: $this->makeLocaleTranslator('fi-FI'),
        );

        self::assertNull($formatter->parseConfirmAnswer(answer: 'y'));
        self::assertNull($formatter->parseConfirmAnswer(answer: 'no'));
    }

    public function testAnswerInBothListsIsRejected(): void
    {
        $config = new IntlConfig(
            defaultLocale: Locale::parse('da-DK'),
        );

        $formatter = $this->makeFormatter(
            translator: new Translator(
                config: $config,
                loader: new InMemoryPhraseLoader(
                    catalogues: [
                        'da-DK' => [
                            'console' => [
                                'input' => [
                                    'confirm' => [
                                        'yesAnswers' => 'j,ja,n',
                                        'noAnswers' => 'n,nej',
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

        self::assertNull($formatter->parseConfirmAnswer(answer: 'n'));
        self::assertSame(
            [
                'yes' => [
                    'j',
                    'ja',
                ],
                'no' => [
                    'nej',
                ],
            ],
            $formatter->confirmAnswers(),
        );

        self::assertSame('[J/nej]', $formatter->forConfirmIndicator(default: true));
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
        $english = CatalogueInspector::leaves(CatalogueInspector::SOURCE_LOCALE, 'console');
        $translated = CatalogueInspector::leaves($locale, 'console');

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

    public function testContainerResolvesFormatterWithoutIntlConfiguration(): void
    {
        self::assertSame(
            '[Y/n]',
            (new Container())->resolve(MessageFormatterInterface::class)->forConfirmIndicator(
                default: true,
            ),
        );
    }
}
