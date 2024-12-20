<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2024 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Container;

/**
 * @template TType
 */
interface DependencyResolverInterface
{
    /**
     * @return TType
     *
     * @throws UnresolvableDependencyException
     */
    public function resolve(Container $container): mixed;
}
