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

namespace Tuxxedo\Container\Resolver\Database;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Database\Driver\ConnectionInterface;

/**
 * @implements DependencyResolverInterface<ConnectionInterface>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class DefaultReadConnection implements DependencyResolverInterface
{
    public function resolve(ContainerInterface $container): ConnectionInterface
    {
        return $container->resolve(ConnectionManagerInterface::class)->getReadConnection();
    }
}
