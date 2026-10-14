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

namespace Tuxxedo\Intl\Loader;

use Tuxxedo\Intl\Locale;

class MergingLoader implements PhraseLoaderInterface
{
    /**
     * @var list<PhraseLoaderInterface>
     */
    private readonly array $loaders;

    public function __construct(
        PhraseLoaderInterface ...$loaders,
    ) {
        $this->loaders = \array_values($loaders);
    }

    public function load(
        Locale $locale,
        string $group,
    ): ?array {
        $merged = null;

        foreach (\array_reverse($this->loaders) as $loader) {
            $result = $loader->load(
                locale: $locale,
                group: $group,
            );

            if ($result === null) {
                continue;
            }

            $merged = $merged === null
                ? $result
                : self::merge(
                    base: $merged,
                    override: $result,
                );
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private static function merge(
        array $base,
        array $override,
    ): array {
        /** @var array<string, mixed>|string $value */
        foreach ($override as $key => $value) {
            /** @var array<string, mixed>|null $existing */
            $existing = $base[$key] ?? null;

            if (\is_array($existing) && \is_array($value)) {
                $base[$key] = self::merge(
                    base: $existing,
                    override: $value,
                );

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
