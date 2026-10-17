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

namespace Tuxxedo\Intl;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: Transliterator::class, lifecycle: Lifecycle::SINGLETON)]
interface TransliteratorInterface
{
    /**
     * @throws TranslationException
     */
    public function transliterate(
        string $value,
        Transliteration $transliteration = Transliteration::ASCII,
    ): string;
}
