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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslationException;

#[DefaultImplementation(class: DefaultPhraseLoader::class, lifecycle: Lifecycle::SINGLETON)]
interface PhraseLoaderInterface
{
    /**
     * @return array<string, mixed>|null
     *
     * @throws TranslationException
     */
    public function load(
        Locale $locale,
        string $group,
    ): ?array;
}
