<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Container\Resolvers;

use Tuxxedo\Container\Container;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\Kernel\Kernel;

/**
 * @implements DependencyResolverInterface<mixed>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ConfigValue implements DependencyResolverInterface
{
    public function __construct(
        protected readonly string $path,
    ) {
    }

    public function resolve(Container $container): mixed
    {
        return $container->resolve(Kernel::class)->config->path($this->path);
    }
}
