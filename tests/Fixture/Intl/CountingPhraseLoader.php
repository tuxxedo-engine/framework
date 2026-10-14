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

class CountingPhraseLoader implements PhraseLoaderInterface
{
    public int $calls = 0;

    public function __construct(
        private readonly PhraseLoaderInterface $loader,
    ) {
    }

    public function load(
        Locale $locale,
        string $group,
    ): ?array {
        ++$this->calls;

        return $this->loader->load(
            locale: $locale,
            group: $group,
        );
    }
}
