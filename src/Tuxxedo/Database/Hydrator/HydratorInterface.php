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

namespace Tuxxedo\Database\Hydrator;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: Hydrator::class, lifecycle: Lifecycle::PERSISTENT)]
interface HydratorInterface
{
    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param array<string, mixed> $values
     * @return TClassName
     *
     * @throws HydrationException
     */
    public function hydrate(
        string $className,
        array $values,
    ): object;
}
