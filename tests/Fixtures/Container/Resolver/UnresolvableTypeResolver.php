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

namespace Fixtures\Container\Resolver;

use Fixtures\Container\UnresolvableDependencyOne;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;

/**
 * @implements DependencyResolverInterface<UnresolvableDependencyOne>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class UnresolvableTypeResolver implements DependencyResolverInterface
{
    public function resolve(ContainerInterface $container): UnresolvableDependencyOne
    {
        return $container->resolve(UnresolvableDependencyOne::class);
    }
}
