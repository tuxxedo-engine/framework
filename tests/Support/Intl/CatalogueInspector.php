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

use Tuxxedo\Intl\Loader\FlatFileLoader;
use Tuxxedo\Intl\Locale;

class CatalogueInspector
{
    public const string RESOURCES_ROOT = __DIR__ . '/../../../resources/intl';

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
