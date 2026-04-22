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

namespace Tuxxedo\Application\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @implements DependencyResolverInterface<string>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class AppVersion implements DependencyResolverInterface
{
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): mixed {
        return $container->resolve(KernelInterface::class)->appVersion;
    }
}
