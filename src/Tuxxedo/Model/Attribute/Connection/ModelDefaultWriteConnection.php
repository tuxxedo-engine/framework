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

namespace Tuxxedo\Model\Attribute\Connection;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\ModelsManager;
use Tuxxedo\Model\ModelsManagerInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @implements DependencyResolverInterface<ModelsManagerInterface>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
readonly class ModelDefaultWriteConnection implements DependencyResolverInterface
{
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): ModelsManagerInterface {
        return new ModelsManager(
            connection: $container->resolve(ConnectionManagerInterface::class)->getWriteConnection(),
            metaData: $container->resolve(MetaDataInterface::class),
        );
    }
}
