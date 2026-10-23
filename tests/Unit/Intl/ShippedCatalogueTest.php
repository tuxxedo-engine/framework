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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Intl\CatalogueInspector;

class ShippedCatalogueTest extends TestCase
{
    public function testEveryLocaleDirectoryIsAKnownLocale(): void
    {
        $known = [
            CatalogueInspector::SOURCE_LOCALE,
            ...CatalogueInspector::AVAILABLE_DEFAULT_LOCALES,
        ];

        \sort($known);

        self::assertSame($known, CatalogueInspector::localeDirectories());
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function localeGroupDataProvider(): \Generator
    {
        $locales = [
            CatalogueInspector::SOURCE_LOCALE,
            ...CatalogueInspector::AVAILABLE_DEFAULT_LOCALES,
        ];

        foreach ($locales as $locale) {
            foreach (CatalogueInspector::groups() as $group) {
                yield $locale . ' ' . $group => [
                    $locale,
                    $group,
                ];
            }
        }
    }

    #[DataProvider('localeGroupDataProvider')]
    public function testPluralKeysDefineEveryCategoryTheLocaleUses(
        string $locale,
        string $group,
    ): void {
        $required = CatalogueInspector::integerPluralCategories($locale);

        self::assertNotEmpty($required);

        foreach (CatalogueInspector::pluralForms($locale, $group) as $base => $categories) {
            foreach ($required as $category) {
                self::assertContains(
                    $category,
                    $categories,
                    \sprintf(
                        '%s/%s: "%s" has no "%s" form',
                        $locale,
                        $group,
                        $base,
                        $category,
                    ),
                );
            }
        }
    }
}
