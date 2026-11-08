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

namespace Unit\Serializer;

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
use Tuxxedo\Serializer\SerializerViolationCode;
use Tuxxedo\Validator\Message\TranslatorMessageFormatter;
use Tuxxedo\Validator\Violation;

class SerializerViolationCodeTest extends TestCase
{
    public function testEveryCodeHasAnEnglishTemplate(): void
    {
        $config = new IntlConfig(
            rootDirectory: '/unused',
            defaultLocale: Locale::parse(CatalogueInspector::SOURCE_LOCALE),
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

        foreach (SerializerViolationCode::cases() as $case) {
            $formatted = $formatter->format(
                violation: new Violation(
                    code: $case,
                    propertyPath: 'probe',
                    invalidValue: 'probe-value',
                ),
            );

            self::assertStringNotContainsString('Validation failed at', $formatted, $case->name);
            self::assertStringContainsString('"probe"', $formatted, $case->name);
        }
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function shippedLocales(): \Generator
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
        $english = CatalogueInspector::leaves(CatalogueInspector::SOURCE_LOCALE, 'serializer');
        $translated = CatalogueInspector::leaves($locale, 'serializer');

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
