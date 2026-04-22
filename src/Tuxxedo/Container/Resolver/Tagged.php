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

namespace Tuxxedo\Container\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @template TClassName of object
 *
 * @implements DependencyResolverInterface<TClassName[]>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class Tagged implements DependencyResolverInterface
{
    /**
     * @param class-string<TClassName> $className
     */
    public function __construct(
        private readonly string $className,
    ) {
    }

    /**
     * @return TClassName[]
     */
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): array {
        return $container->resolveTagged($this->className);
    }
}
