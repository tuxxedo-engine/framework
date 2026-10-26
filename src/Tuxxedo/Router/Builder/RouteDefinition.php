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
use Tuxxedo\Router\RoutePriority;

readonly class RouteDefinition
{
    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function __construct(
        public ?Method $method,
        public string $uri,
        public string $controller,
        public string $action,
        public ?string $name,
        public array $middleware,
        public RoutePriority $priority,
    ) {
    }
}
