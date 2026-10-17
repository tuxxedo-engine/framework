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

#[DefaultImplementation(class: Collator::class, lifecycle: Lifecycle::SINGLETON)]
interface CollatorInterface
{
    /**
     * @throws TranslationException
     */
    public function compare(
        string $first,
        string $second,
        ?LocaleInterface $locale = null,
    ): int;

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $values
     * @return array<TKey, TValue>
     *
     * @throws TranslationException
     */
    public function sort(
        array $values,
        ?LocaleInterface $locale = null,
    ): array;

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $values
     * @return array<TKey, TValue>
     *
     * @throws TranslationException
     */
    public function sortKeys(
        array $values,
        ?LocaleInterface $locale = null,
    ): array;
}
