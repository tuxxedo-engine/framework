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

class CachingLoader implements PhraseLoaderInterface
{
    /**
     * @var array<string, array<string, array<string, mixed>|null>>
     */
    private array $cache = [];

    public function __construct(
        private readonly PhraseLoaderInterface $loader,
    ) {
    }

    public function load(
        LocaleInterface $locale,
        string $group,
    ): ?array {
        $tag = $locale->tag;

        if (!isset($this->cache[$tag]) || !\array_key_exists($group, $this->cache[$tag])) {
            $this->cache[$tag][$group] = $this->loader->load(
                locale: $locale,
                group: $group,
            );
        }

        return $this->cache[$tag][$group];
    }

    public function sourceOf(
        LocaleInterface $locale,
        string $group,
        array $segments,
    ): ?string {
        return $this->loader->sourceOf(
            locale: $locale,
            group: $group,
            segments: $segments,
        );
    }
}
