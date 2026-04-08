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

use Tuxxedo\Collection\CollectionInterface;
use Tuxxedo\Collection\ImmutableCollection;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;

/**
 * @implements DependencyResolverInterface<ImmutableCollection<int, object>>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class TaggedCollection implements DependencyResolverInterface
{
    /**
     * @param class-string $className
     */
    public function __construct(
        private readonly string $className,
    ) {
    }

    public function resolve(
        ContainerInterface $container,
        \ReflectionParameter $parameter,
    ): CollectionInterface {
        /** @var ImmutableCollection<int, object> */
        return new ImmutableCollection($container->resolveTagged($this->className));
    }
}
