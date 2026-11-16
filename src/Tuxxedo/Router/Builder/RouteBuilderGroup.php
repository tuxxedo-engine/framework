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

class RouteBuilderGroup implements RouteBuilderGroupInterface
{
    /**
     * @param \Closure(RouteDefinition): void $sink
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function __construct(
        private readonly \Closure $sink,
        private readonly string $path,
        private readonly array $middleware,
        private readonly string $namePrefix = '',
        private readonly ?string $prefix = null,
        private readonly bool $trailingSlash = false,
    ) {
    }

    public static function joinPath(
        string $prefix,
        string $child,
    ): string {
        if ($child === '') {
            return $prefix;
        }

        return \rtrim($prefix, '/') . '/' . \ltrim($child, '/');
    }

    public function get(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::GET,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function post(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::POST,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function put(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::PUT,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function patch(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::PATCH,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function delete(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::DELETE,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function options(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::OPTIONS,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function head(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::HEAD,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function connect(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::CONNECT,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function trace(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::TRACE,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function query(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [
                Method::QUERY,
            ],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function any(
        string $path,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $this->push(
            methods: [],
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

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
    ): static {
        $this->push(
            methods: $methods,
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        return $this;
    }

    public function group(
        string $path,
        array $middleware,
        \Closure $callback,
        ?string $name = null,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $nested = new RouteBuilderGroup(
            sink: $this->sink,
            path: self::joinPath(
                prefix: $this->path,
                child: $path,
            ),
            middleware: [
                ...$this->middleware,
                ...$middleware,
            ],
            namePrefix: $this->namePrefix . ($name ?? ''),
            prefix: $prefix ?? $this->prefix,
            trailingSlash: $trailingSlash || $this->trailingSlash,
        );

        $callback($nested);

        return $this;
    }

    /**
     * @param list<Method> $methods
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    private function push(
        array $methods,
        string $path,
        string $controller,
        string $action,
        ?string $name,
        array $middleware,
        RoutePriority $priority,
        ?string $prefix,
        bool $trailingSlash,
    ): void {
        $resolvedName = $name !== null && $this->namePrefix !== ''
            ? $this->namePrefix . $name
            : $name;

        ($this->sink)(
            new RouteDefinition(
                methods: $methods,
                path: self::joinPath(
                    prefix: $this->path,
                    child: $path,
                ),
                controller: $controller,
                action: $action,
                name: $resolvedName,
                middleware: [
                    ...$this->middleware,
                    ...$middleware,
                ],
                priority: $priority,
                prefix: $prefix ?? $this->prefix,
                trailingSlash: $trailingSlash || $this->trailingSlash,
            ),
        );
    }
}
