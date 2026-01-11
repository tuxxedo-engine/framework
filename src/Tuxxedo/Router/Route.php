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
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;

readonly class Route implements RouteInterface
{
    public ?Method $method;

    /**
     * @param class-string $controller
     * @param array<(\Closure(): MiddlewareInterface)> $middleware
     * @param RouteArgumentInterface[] $arguments
     */
    public function __construct(
        Method|string|null $method,
        public string $uri,
        public string $controller,
        public string $action,
        public ?string $name = null,
        public array $middleware = [],
        public RoutePriority $priority = RoutePriority::NORMAL,
        public ?string $regexUri = null,
        public array $arguments = [],
    ) {
        if (\is_string($method)) {
            $method = Method::from($method);
        }

        $this->method = $method;
    }
}
