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

namespace Support\Intl;

use Tuxxedo\Intl\CldrCategory;
use Tuxxedo\Intl\Loader\FlatFileLoader;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\PluralSelector;

class CatalogueInspector
{
    public const string RESOURCES_ROOT = __DIR__ . '/../../../resources/intl';
    public const string SOURCE_LOCALE = 'en-US';

    public const array AVAILABLE_DEFAULT_LOCALES = [
        'da-DK',
        'fi-FI',
        'sv-SE',
        'nb-NO',
        'nn-NO',
        'is-IS',
        'fo-FO',
    ];

    /**
     * @return list<string>
     */
    public static function localeDirectories(): array
    {
        $directories = \glob(self::RESOURCES_ROOT . '/*', \GLOB_ONLYDIR);
        $locales = \array_map(
            \basename(...),
            $directories !== false ? $directories : [],
        );

        \sort($locales);

        return $locales;
    }

    /**
     * @return list<string>
     */
    public static function groups(): array
    {
        $files = \glob(self::RESOURCES_ROOT . '/' . self::SOURCE_LOCALE . '/*.php');
        $groups = \array_map(
            static fn (string $file): string => \basename($file, '.php'),
            $files !== false ? $files : [],
        );

        \sort($groups);

        return $groups;
    }

    /**
     * @return list<string>
     */
    public static function integerPluralCategories(
        string $locale,
    ): array {
        $selector = new PluralSelector();
        $parsed = Locale::parse($locale);
        $found = [];

        for ($count = 0; $count <= 1000; $count++) {
            $found[$selector->select($parsed, $count)->value] = true;
        }

        $categories = [];

        foreach (CldrCategory::cases() as $category) {
            if (isset($found[$category->value])) {
                $categories[] = $category->value;
            }
        }

        return $categories;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function pluralForms(
        string $locale,
        string $group,
    ): array {
        $categories = \array_map(
            static fn (CldrCategory $category): string => $category->value,
            CldrCategory::cases(),
        );

        $forms = [];

        foreach (\array_keys(self::leaves($locale, $group)) as $path) {
            $position = \strrpos($path, '.');

            if ($position === false) {
                continue;
            }

            $category = \substr($path, $position + 1);

            if (\in_array($category, $categories, true)) {
                $forms[\substr($path, 0, $position)][] = $category;
            }
        }

        return $forms;
    }

    /**
     * @return array<string, string>
     */
    public static function leaves(
        string $locale,
        string $group,
    ): array {
        $catalogue = (new FlatFileLoader(
            rootDirectory: self::RESOURCES_ROOT,
        ))->load(
            locale: Locale::parse($locale),
            group: $group,
        );

        return self::flatten(
            catalogue: $catalogue ?? [],
        );
    }

    /**
     * @return list<string>
     */
    public static function placeholders(
        string $value,
    ): array {
        \preg_match_all('/\{(\w+)\}/', $value, $matches);

        $names = $matches[1];

        \sort($names);

        return $names;
    }

    /**
     * @param array<string, mixed> $catalogue
     *
     * @return array<string, string>
     */
    private static function flatten(
        array $catalogue,
        string $prefix = '',
    ): array {
        $flat = [];

        foreach ($catalogue as $key => $value) {
            $path = $prefix . $key;

            if (\is_array($value)) {
                /** @var array<string, mixed> $nested */
                $nested = $value;

                $flat = [
                    ...$flat,
                    ...self::flatten(
                        catalogue: $nested,
                        prefix: $path . '.',
                    ),
                ];

                continue;
            }

            $flat[$path] = \is_string($value)
                ? $value
                : '';
        }

        \ksort($flat);

        return $flat;
    }
}
