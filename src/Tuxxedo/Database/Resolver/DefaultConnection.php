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

namespace Tuxxedo\Database\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Database\Driver\ConnectionInterface;

/**
 * @implements DependencyResolverInterface<ConnectionInterface>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class DefaultConnection implements DependencyResolverInterface
{
    public function resolve(
        ContainerInterface $container,
        \ReflectionParameter $parameter,
    ): ConnectionInterface {
        return $container->resolve(ConnectionManagerInterface::class)->getDefaultConnection();
    }
}
