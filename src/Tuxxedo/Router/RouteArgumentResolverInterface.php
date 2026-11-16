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

namespace Tuxxedo\Router;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: RouteArgumentResolver::class, lifecycle: Lifecycle::SINGLETON)]
interface RouteArgumentResolverInterface
{
    /**
     * @throws RouterException
     */
    public function resolve(
        string $path,
        ?PrefixInterface $prefix,
        \ReflectionMethod $method,
    ): ResolvedPathInterface;
}
