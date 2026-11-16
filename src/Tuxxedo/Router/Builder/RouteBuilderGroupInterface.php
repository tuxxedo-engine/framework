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

interface RouteBuilderGroupInterface
{
    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function get(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function post(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function put(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function patch(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function delete(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function options(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function head(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function connect(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function trace(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function query(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function any(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param list<Method> $methods
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function match(
        array $methods,
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;

    /**
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param \Closure(RouteBuilderGroupInterface): void $callback
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function group(
        string $path,
        array $middleware,
        \Closure $callback,
        ?string $name = null,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static;
}
