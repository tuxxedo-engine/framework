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
use Tuxxedo\Intl\Locale;

class InMemoryPhraseLoader implements PhraseLoaderInterface
{
    /**
     * @param array<string, array<string, array<string, mixed>|null>> $catalogues
     */
    public function __construct(
        private readonly array $catalogues,
    ) {
    }

    public function load(
        Locale $locale,
        string $group,
    ): ?array {
        return $this->catalogues[$locale->tag][$group] ?? null;
    }
}
