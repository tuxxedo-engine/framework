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

use Tuxxedo\Console\Console;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;

/**
 * @implements DependencyResolverInterface<string>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class CliAppVersion implements DependencyResolverInterface
{
    public function resolve(ContainerInterface $container): mixed
    {
        return $container->resolve(Console::class)->appVersion;
    }
}
