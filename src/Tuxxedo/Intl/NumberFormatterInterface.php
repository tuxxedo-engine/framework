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

#[DefaultImplementation(class: NumberFormatter::class, lifecycle: Lifecycle::SINGLETON)]
interface NumberFormatterInterface
{
    /**
     * @throws TranslationException
     */
    public function format(
        int|float $value,
        LocaleInterface $locale,
        int $decimals = 0,
    ): string;
}
