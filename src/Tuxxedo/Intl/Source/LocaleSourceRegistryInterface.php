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

namespace Tuxxedo\Intl\Source;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: LocaleSourceRegistry::class, lifecycle: Lifecycle::SINGLETON)]
interface LocaleSourceRegistryInterface
{
    public function register(
        SourcePriority $priority,
        LocaleSourceInterface $source,
    ): void;

    /**
     * @return list<LocaleSourceInterface>
     */
    public function sortedSources(): array;
}
