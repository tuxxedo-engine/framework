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
    public LocaleInterface $locale {
        get;
    }

    /**
     * @var list<LocaleInterface>
     */
    public array $supportedLocales {
        get;
    }

    /**
     * @param array<string, scalar|null> $replacements
     *
     * @throws TranslationException
     */
    public function translate(
        string $key,
        array $replacements = [],
        ?LocaleInterface $locale = null,
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
        ?LocaleInterface $locale = null,
    ): string;

    public function has(
        string $key,
        ?LocaleInterface $locale = null,
    ): bool;

    /**
     * @throws TranslationException
     */
    public function sourceOf(
        string $key,
        ?LocaleInterface $locale = null,
    ): ?string;

    public function withLocale(
        LocaleInterface $locale,
    ): self;
}
