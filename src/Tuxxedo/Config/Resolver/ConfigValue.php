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

namespace Tuxxedo\Config\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @implements DependencyResolverInterface<mixed>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class ConfigValue implements DependencyResolverInterface
{
    public function __construct(
        protected readonly string $path,
    ) {
    }

    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): mixed {
        return $container->resolve(KernelInterface::class)->config->path($this->path);
    }
}
