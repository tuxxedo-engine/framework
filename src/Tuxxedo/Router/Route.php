<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Router;

use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Handler\RequestHandlerInterface;

class Route implements RouteInterface
{
    /**
     * @param class-string $controller
     * @param array<(\Closure(): RequestHandlerInterface)> $middleware
     */
    public function __construct(
        public readonly ?Method $method,
        public readonly string $uri,
        public readonly string $controller,
        public readonly string $action,
        public readonly array $middleware = [],
        public readonly RoutePriority $priority = RoutePriority::NORMAL,
    ) {
    }
}
