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

namespace Unit\Validator\Message;

use Fixture\Intl\InMemoryPhraseLoader;
use Fixture\Validator\FixtureBackedEnumContext;
use Fixture\Validator\FixtureMixedTypesContext;
use Fixture\Validator\FixtureReceivedContext;
use Fixture\Validator\FixtureUnitEnum;
use Fixture\Validator\FixtureUnitEnumContext;
use Fixture\Validator\FixtureViolationCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Intl\CatalogueInspector;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\MissingKeyMode;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\Validator\Message\TranslatorMessageFormatter;
use Tuxxedo\Validator\Violation;

class TranslatorMessageFormatterTest extends TestCase
{
    /**
     * @param array<string, array<string, array<string, mixed>|null>> $catalogues
     */
    private function makeTranslator(
        array $catalogues = [],
        MissingKeyMode $missingKeyMode = MissingKeyMode::PASSTHROUGH,
    ): TranslatorInterface {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse('en-US'),
            missingKeyMode: $missingKeyMode,
        );

        return new Translator(
            config: $config,
            loader: new InMemoryPhraseLoader(
                catalogues: $catalogues,
            ),
            resolver: new StaticLocaleResolver(
                config: $config,
            ),
            pluralSelector: new PluralSelector(),
        );
    }

    public function testFormatFallsBackWhenCatalogueMissingKey(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'validator' => [
                            'fallback' => 'Validation failed at "{path}" ({code})',
                        ],
                    ],
                ],
            ),
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'field',
                invalidValue: 'x',
            ),
        );

        self::assertStringContainsString('field', $formatted);
        self::assertStringContainsString('fixture.always-fail', $formatted);
    }

    public function testFormatInterpolatesTemplateWithPathAndValue(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'fixture' => [
                            'always-fail' => 'Field {path} rejected value {value}',
                        ],
                    ],
                ],
            ),
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'email',
                invalidValue: 'nope',
            ),
        );

        self::assertSame('Field email rejected value nope', $formatted);
    }

    public function testFormatSpreadsScalarContextIntoPlaceholders(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'fixture' => [
                            'odd-number' => 'Got odd number {received} at {path}',
                        ],
                    ],
                ],
            ),
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ODD_NUMBER,
                propertyPath: 'age',
                invalidValue: 7,
                context: new FixtureReceivedContext(
                    received: 7,
                ),
            ),
        );

        self::assertSame('Got odd number 7 at age', $formatted);
    }

    public function testFormatRendersBackedEnumContextValue(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'fixture' => [
                            'always-fail' => 'status={status}',
                        ],
                    ],
                ],
            ),
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'field',
                invalidValue: 'x',
                context: new FixtureBackedEnumContext(
                    status: FixtureViolationCode::ALWAYS_FAIL,
                ),
            ),
        );

        self::assertSame('status=fixture.always-fail', $formatted);
    }

    public function testFormatRendersUnitEnumContextName(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'fixture' => [
                            'always-fail' => 'tag={tag}',
                        ],
                    ],
                ],
            ),
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'field',
                invalidValue: 'x',
                context: new FixtureUnitEnumContext(
                    tag: FixtureUnitEnum::BETA,
                ),
            ),
        );

        self::assertSame('tag=BETA', $formatted);
    }

    public function testFormatRendersMixedTypesContext(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'fixture' => [
                            'always-fail' => '{nullish}|{flag}|{list}|{obj}',
                        ],
                    ],
                ],
            ),
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'field',
                invalidValue: 'x',
                context: new FixtureMixedTypesContext(
                    nullish: null,
                    flag: true,
                    list: [
                        'a',
                        'b',
                    ],
                    obj: new \stdClass(),
                ),
            ),
        );

        self::assertSame('null|true|a, b|stdClass', $formatted);
    }

    public function testFormatRendersTranslationWhoseTextEqualsItsKey(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'fixture' => [
                            'always-fail' => 'fixture.always-fail',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            'fixture.always-fail',
            $formatter->format(
                violation: new Violation(
                    code: FixtureViolationCode::ALWAYS_FAIL,
                    propertyPath: 'field',
                    invalidValue: 'x',
                ),
            ),
        );
    }

    public function testFormatFallsBackForMissingKeyUnderPassthrough(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'validator' => [
                            'fallback' => 'Validation failed at "{path}" ({code})',
                        ],
                    ],
                ],
                missingKeyMode: MissingKeyMode::PASSTHROUGH,
            ),
        );

        self::assertSame(
            'Validation failed at "field" (fixture.always-fail)',
            $formatter->format(
                violation: new Violation(
                    code: FixtureViolationCode::ALWAYS_FAIL,
                    propertyPath: 'field',
                    invalidValue: 'x',
                ),
            ),
        );
    }

    public function testFormatReturnsMarkerForMissingKeyUnderMarker(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                missingKeyMode: MissingKeyMode::MARKER,
            ),
        );

        self::assertSame(
            '[missing:fixture.always-fail]',
            $formatter->format(
                violation: new Violation(
                    code: FixtureViolationCode::ALWAYS_FAIL,
                    propertyPath: 'field',
                    invalidValue: 'x',
                ),
            ),
        );
    }

    public function testFormatThrowsForMissingKeyUnderStrict(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                missingKeyMode: MissingKeyMode::STRICT,
            ),
        );

        self::expectException(TranslationException::class);

        $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'field',
                invalidValue: 'x',
            ),
        );
    }

    public function testFormatFallbackFollowsTheLocale(): void
    {
        $formatter = new TranslatorMessageFormatter(
            translator: $this->makeTranslator(
                catalogues: [
                    'en-US' => [
                        'validator' => [
                            'fallback' => 'Validation failed at "{path}" ({code})',
                        ],
                    ],
                    'da-DK' => [
                        'validator' => [
                            'fallback' => 'Validering fejlede ved "{path}" ({code})',
                        ],
                    ],
                ],
            )->withLocale(Locale::parse('da-DK')),
        );

        self::assertSame(
            'Validering fejlede ved "field" (fixture.always-fail)',
            $formatter->format(
                violation: new Violation(
                    code: FixtureViolationCode::ALWAYS_FAIL,
                    propertyPath: 'field',
                    invalidValue: 'x',
                ),
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
        $english = CatalogueInspector::leaves('en-US', 'validator');
        $translated = CatalogueInspector::leaves($locale, 'validator');

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
}
