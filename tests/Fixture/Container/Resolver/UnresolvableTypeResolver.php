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

namespace Fixture\Container\Resolver;

use Fixture\Container\UnresolvableDependencyOne;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @implements DependencyResolverInterface<UnresolvableDependencyOne>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class UnresolvableTypeResolver implements DependencyResolverInterface
{
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): UnresolvableDependencyOne {
        return $container->resolve(UnresolvableDependencyOne::class);
    }
}
