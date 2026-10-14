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

#[DefaultImplementation(class: Translator::class, lifecycle: Lifecycle::SINGLETON)]
interface TranslatorInterface
{
    /**
     * @param array<string, scalar|null> $replacements
     *
     * @throws TranslationException
     */
    public function translate(
        string $key,
        array $replacements = [],
        ?Locale $locale = null,
    ): string;

    /**
     * @param array<string, scalar|null> $replacements
     *
     * @throws TranslationException
     */
    public function plural(
        string $key,
        int $count,
        array $replacements = [],
        ?Locale $locale = null,
    ): string;

    public function has(
        string $key,
        ?Locale $locale = null,
    ): bool;

    public function withLocale(
        Locale $locale,
    ): self;
}
