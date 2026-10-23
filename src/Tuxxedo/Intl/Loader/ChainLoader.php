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

use Tuxxedo\Intl\LocaleInterface;

class ChainLoader implements PhraseLoaderInterface
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
        LocaleInterface $locale,
        string $group,
    ): ?array {
        foreach ($this->loaders as $loader) {
            $result = $loader->load(
                locale: $locale,
                group: $group,
            );

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    public function sourceOf(
        LocaleInterface $locale,
        string $group,
        array $segments,
    ): ?string {
        foreach ($this->loaders as $loader) {
            $catalogue = $loader->load(
                locale: $locale,
                group: $group,
            );

            if ($catalogue !== null) {
                return $loader->sourceOf(
                    locale: $locale,
                    group: $group,
                    segments: $segments,
                );
            }
        }

        return null;
    }
}
