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

use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\RoutePriority;

interface RouteBuilderGroupInterface
{
    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function get(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function post(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function put(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function patch(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function delete(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function options(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function head(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function connect(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function trace(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function query(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function any(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static;

    /**
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param \Closure(RouteBuilderGroupInterface): void $callback
     */
    public function group(
        string $uri,
        array $middleware,
        \Closure $callback,
    ): static;
}
