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

class RouteBuilderGroup implements RouteBuilderGroupInterface
{
    /**
     * @param \Closure(RouteDefinition): void $sink
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    public function __construct(
        private readonly \Closure $sink,
        private readonly string $prefix,
        private readonly array $middleware,
    ) {
    }

    public function get(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::GET,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function post(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::POST,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function put(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::PUT,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function patch(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::PATCH,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function delete(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::DELETE,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function options(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::OPTIONS,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function head(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::HEAD,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function connect(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::CONNECT,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function trace(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::TRACE,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function query(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: Method::QUERY,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function any(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        $this->push(
            method: null,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    public function group(
        string $uri,
        array $middleware,
        \Closure $callback,
    ): static {
        $nested = new RouteBuilderGroup(
            sink: $this->sink,
            prefix: self::joinUri(
                prefix: $this->prefix,
                child: $uri,
            ),
            middleware: [
                ...$this->middleware,
                ...$middleware,
            ],
        );

        $callback($nested);

        return $this;
    }

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    private function push(
        ?Method $method,
        string $uri,
        string $controller,
        string $action,
        ?string $name,
        array $middleware,
        RoutePriority $priority,
    ): void {
        ($this->sink)(
            new RouteDefinition(
                method: $method,
                uri: self::joinUri(
                    prefix: $this->prefix,
                    child: $uri,
                ),
                controller: $controller,
                action: $action,
                name: $name,
                middleware: [
                    ...$this->middleware,
                    ...$middleware,
                ],
                priority: $priority,
            ),
        );
    }

    public static function joinUri(
        string $prefix,
        string $child,
    ): string {
        if ($child === '') {
            return $prefix;
        }

        return \rtrim($prefix, '/') . '/' . \ltrim($child, '/');
    }
}
