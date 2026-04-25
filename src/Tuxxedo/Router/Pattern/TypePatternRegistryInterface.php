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

namespace Tuxxedo\Router\Pattern;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: TypePatternRegistry::class, lifecycle: Lifecycle::PERSISTENT)]
interface TypePatternRegistryInterface
{
    public function has(string $name): bool;

    public function get(string $name): ?TypePatternInterface;
}
