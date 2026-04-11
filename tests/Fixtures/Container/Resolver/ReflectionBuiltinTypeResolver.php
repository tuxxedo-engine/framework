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

namespace Fixtures\Container\Resolver;

use Fixtures\Container\ReflectionResolverResult;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Container\Reflection\Parameter;
use Tuxxedo\Container\Reflection\ParameterInterface;

/**
 * @implements DependencyResolverInterface<ReflectionResolverResult>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ReflectionBuiltinTypeResolver implements DependencyResolverInterface
{
    public function resolve(
        ContainerInterface $container,
        ParameterInterface $parameter,
    ): ReflectionResolverResult {
        return new ReflectionResolverResult(
            new Parameter($parameter->reflector->getDeclaringFunction()->getParameters()[1])->getBuiltinType(),
        );
    }
}
