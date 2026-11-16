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

namespace Tuxxedo\Router\Builder;

use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\PrefixInterface;
use Tuxxedo\Router\RoutePriority;

readonly class RouteDefinition
{
    /**
     * @param list<Method> $methods
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function __construct(
        public array $methods,
        public string $path,
        public string $controller,
        public string $action,
        public ?string $name,
        public array $middleware,
        public RoutePriority $priority,
        public ?string $prefix,
        public bool $trailingSlash,
    ) {
    }
}
