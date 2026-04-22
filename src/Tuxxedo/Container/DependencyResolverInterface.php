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

namespace Tuxxedo\Container;

use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @template TType
 */
interface DependencyResolverInterface
{
    /**
     * @return TType
     *
     * @throws ContainerException
     */
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): mixed;
}
