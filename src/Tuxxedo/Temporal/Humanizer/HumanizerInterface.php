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

namespace Tuxxedo\Temporal\Humanizer;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\InstantInterface;

#[DefaultImplementation(class: Humanizer::class, lifecycle: Lifecycle::SINGLETON)]
interface HumanizerInterface
{
    public function withLocale(
        ?LocaleInterface $locale,
    ): static;

    public function diffForHumans(
        InstantInterface $moment,
        ?InstantInterface $reference = null,
    ): string;

    public function formatDuration(
        DurationInterface $duration,
        HumanizedStyle $style = HumanizedStyle::LONG,
    ): string;
}
