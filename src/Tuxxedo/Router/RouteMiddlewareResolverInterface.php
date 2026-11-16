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

namespace Tuxxedo\Router;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;

#[DefaultImplementation(class: RouteMiddlewareResolver::class, lifecycle: Lifecycle::SINGLETON)]
interface RouteMiddlewareResolverInterface
{
    /**
     * @param \ReflectionClass<object>|\ReflectionMethod $reflector
     * @return list<\Closure(): MiddlewareInterface>
     */
    public function attributes(
        \ReflectionClass|\ReflectionMethod $reflector,
    ): array;

    /**
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @return list<\Closure(): MiddlewareInterface>
     */
    public function normalize(
        array $middleware,
    ): array;
}
