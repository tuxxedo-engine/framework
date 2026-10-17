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

#[DefaultImplementation(class: DateFormatter::class, lifecycle: Lifecycle::SINGLETON)]
interface DateFormatterInterface
{
    /**
     * @throws TranslationException
     */
    public function format(
        \DateTimeInterface $value,
        LocaleInterface $locale,
        DateStyle $dateStyle,
        DateStyle $timeStyle,
    ): string;
}
