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

namespace Fixture\Intl;

use Tuxxedo\Intl\Loader\PhraseLoaderInterface;
use Tuxxedo\Intl\LocaleInterface;

class InMemoryPhraseLoader implements PhraseLoaderInterface
{
    /**
     * @param array<string, array<string, array<string, mixed>|null>> $catalogues
     */
    public function __construct(
        private readonly array $catalogues,
        private readonly ?string $source = null,
    ) {
    }

    public function load(
        LocaleInterface $locale,
        string $group,
    ): ?array {
        return $this->catalogues[$locale->tag][$group] ?? null;
    }

    public function sourceOf(
        LocaleInterface $locale,
        string $group,
        array $segments,
    ): ?string {
        $cursor = $this->catalogues[$locale->tag][$group] ?? null;

        foreach ($segments as $segment) {
            if (!\is_array($cursor) || !\array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return \is_string($cursor)
            ? $this->source
            : null;
    }
}
