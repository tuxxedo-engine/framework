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

/**
 * @implements DependencyResolverInterface<Container>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class AppContainer implements DependencyResolverInterface
{
    public function resolve(Container $container): mixed
    {
        return $container;
    }
}
