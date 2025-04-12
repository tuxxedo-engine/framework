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

use Tuxxedo\Application\Application;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\DependencyResolverInterface;

/**
 * @implements DependencyResolverInterface<string>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class AppName implements DependencyResolverInterface
{
    public function resolve(Container $container): mixed
    {
        return $container->resolve(Application::class)->appName;
    }
}
