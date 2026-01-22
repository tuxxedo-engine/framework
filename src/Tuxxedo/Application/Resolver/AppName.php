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

namespace Tuxxedo\Application\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\Kernel\KernelInterface;

/**
 * @implements DependencyResolverInterface<string>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class AppName implements DependencyResolverInterface
{
    public function resolve(
        ContainerInterface $container,
        \ReflectionParameter $parameter,
    ): mixed {
        return $container->resolve(KernelInterface::class)->appName;
    }
}
