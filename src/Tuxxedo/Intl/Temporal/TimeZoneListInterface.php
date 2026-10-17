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

namespace Tuxxedo\Intl\Temporal;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\TranslationException;

#[DefaultImplementation(class: TimeZoneList::class, lifecycle: Lifecycle::SINGLETON)]
interface TimeZoneListInterface
{
    /**
     * @return list<TimeZoneEntryInterface>
     *
     * @throws TranslationException
     */
    public function all(
        LocaleInterface $locale,
        ?string $country = null,
        ?\DateTimeInterface $at = null,
    ): array;

    /**
     * @return list<TimeZoneGroupInterface>
     *
     * @throws TranslationException
     */
    public function grouped(
        LocaleInterface $locale,
        ?\DateTimeInterface $at = null,
    ): array;

    /**
     * @param list<string> $names
     * @return list<TimeZoneEntryInterface>
     *
     * @throws TranslationException
     */
    public function of(
        array $names,
        LocaleInterface $locale,
        ?\DateTimeInterface $at = null,
    ): array;
}
