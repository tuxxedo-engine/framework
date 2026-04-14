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
use Tuxxedo\Reflection\ParameterInterface;

/**
 * @implements DependencyResolverInterface<mixed>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Glue implements DependencyResolverInterface
{
    public function __construct(
        private readonly \Closure $binder,
    ) {
    }

    public function resolve(
        ContainerInterface $container,
        ParameterInterface $parameter,
    ): mixed {
        /** @var \Closure(): mixed */
        $binder = $this->binder;

        return $container->call(
            $binder,
            [
                'parameter' => $parameter,
            ],
        );
    }
}
