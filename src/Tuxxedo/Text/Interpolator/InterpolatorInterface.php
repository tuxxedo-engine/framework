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

namespace Tuxxedo\Text\Interpolator;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: Interpolator::class, lifecycle: Lifecycle::SINGLETON)]
interface InterpolatorInterface
{
    /**
     * @param array<string, string> $replacements
     */
    public function interpolate(
        string $template,
        array $replacements,
    ): string;
}
