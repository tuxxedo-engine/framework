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

namespace Tuxxedo\Http\Kernel\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @implements DependencyResolverInterface<UrlInterface>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class AppUrl implements DependencyResolverInterface
{
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): UrlInterface {
        return $container->resolve(KernelInterface::class)->appUrl;
    }
}
