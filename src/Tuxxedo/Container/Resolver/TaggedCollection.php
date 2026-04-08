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
use Tuxxedo\Container\Reflection\ParameterInterface;

/**
 * @template TClassName of object
 *
 * @implements DependencyResolverInterface<ImmutableCollection<int, TClassName>>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class TaggedCollection implements DependencyResolverInterface
{
    /**
     * @param class-string<TClassName> $className
     */
    public function __construct(
        private readonly string $className,
    ) {
    }

    public function resolve(
        ContainerInterface $container,
        ParameterInterface $parameter,
    ): CollectionInterface {
        /** @var ImmutableCollection<int, TClassName> */
        return new ImmutableCollection($container->resolveTagged($this->className));
    }
}
