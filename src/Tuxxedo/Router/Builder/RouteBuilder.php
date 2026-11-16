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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\Compiler\PathCompiler;
use Tuxxedo\Router\Compiler\PathCompilerInterface;
use Tuxxedo\Router\Pattern\TypePatternRegistry;
use Tuxxedo\Router\Pattern\TypePatternRegistryInterface;
use Tuxxedo\Router\Prefix;
use Tuxxedo\Router\PrefixInterface;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\RouteArgumentResolver;
use Tuxxedo\Router\RouteArgumentResolverInterface;
use Tuxxedo\Router\RouteInterface;
use Tuxxedo\Router\RouteMiddlewareResolver;
use Tuxxedo\Router\RouteMiddlewareResolverInterface;
use Tuxxedo\Router\RoutePriority;
use Tuxxedo\Router\RouterException;

class RouteBuilder implements RouteBuilderInterface
{
    /**
     * @var list<RouteDefinition>
     */
    private array $definitions = [];

    private readonly RouteArgumentResolverInterface $argumentResolver;
    private readonly RouteMiddlewareResolverInterface $middlewareResolver;

    public function __construct(
        private readonly ContainerInterface $container,
        ?TypePatternRegistryInterface $patterns = null,
        ?PathCompilerInterface $pathCompiler = null,
        ?RouteArgumentResolverInterface $argumentResolver = null,
        ?RouteMiddlewareResolverInterface $middlewareResolver = null,
    ) {
        $this->argumentResolver = $argumentResolver ?? new RouteArgumentResolver(
            pathCompiler: $pathCompiler ?? new PathCompiler(
                patterns: $patterns ?? TypePatternRegistry::createDefault(),
            ),
        );
        $this->middlewareResolver = $middlewareResolver ?? new RouteMiddlewareResolver(
            container: $this->container,
        );
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
        return $this->addDefinition(
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
    }

    public function group(
        string $path,
        array $middleware,
        \Closure $callback,
        ?string $name = null,
        ?string $prefix = null,
        bool $trailingSlash = false,
    ): static {
        $group = new RouteBuilderGroup(
            sink: function (RouteDefinition $definition): void {
                $this->definitions[] = $definition;
            },
            path: $path,
            middleware: $middleware,
            namePrefix: $name ?? '',
            prefix: $prefix,
            trailingSlash: $trailingSlash,
        );

        $callback($group);

        return $this;
    }

    public function build(): array
    {
        $routes = [];
        $seenNames = [];

        foreach ($this->definitions as $definition) {
            if ($definition->name !== null) {
                if (\array_key_exists($definition->name, $seenNames)) {
                    throw RouterException::fromDuplicateRouteName(
                        className: $definition->controller,
                        method: $definition->action,
                        name: $definition->name,
                    );
                }

                $seenNames[$definition->name] = true;
            }

            $prefix = $this->prefix($definition->prefix);
            $path = $prefix !== null
                ? RouteBuilderGroup::joinPath(
                    prefix: $prefix->path,
                    child: $definition->path,
                )
                : $definition->path;

            $method = new \ReflectionMethod(
                objectOrMethod: $definition->controller,
                method: $definition->action,
            );

            $middleware = [
                ...$this->middlewareResolver->normalize($definition->middleware),
                ...$this->middlewareResolver->attributes($method->getDeclaringClass()),
                ...$this->middlewareResolver->attributes($method),
            ];

            $routes = [
                ...$routes,
                ...$this->routes(
                    definition: $definition,
                    name: $definition->name,
                    path: $path,
                    prefix: $prefix,
                    method: $method,
                    middleware: $middleware,
                ),
            ];

            if ($definition->trailingSlash && !\str_ends_with($path, '/')) {
                $routes = [
                    ...$routes,
                    ...$this->routes(
                        definition: $definition,
                        name: null,
                        path: $path . '/',
                        prefix: $prefix,
                        method: $method,
                        middleware: $middleware,
                    ),
                ];
            }
        }

        return $routes;
    }

    /**
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    private function prefix(
        ?string $prefix,
    ): ?PrefixInterface {
        if ($prefix === null) {
            return null;
        }

        return \is_a($prefix, PrefixInterface::class, true)
            ? $this->container->resolve($prefix)
            : new Prefix($prefix);
    }

    /**
     * @param list<\Closure(): MiddlewareInterface> $middleware
     * @return list<RouteInterface>
     *
     * @throws RouterException
     */
    private function routes(
        RouteDefinition $definition,
        ?string $name,
        string $path,
        ?PrefixInterface $prefix,
        \ReflectionMethod $method,
        array $middleware,
    ): array {
        $resolved = $this->argumentResolver->resolve(
            path: $path,
            prefix: $prefix,
            method: $method,
        );

        if (\sizeof($definition->methods) === 0) {
            return [
                new Route(
                    method: null,
                    path: $path,
                    controller: $definition->controller,
                    action: $definition->action,
                    name: $name,
                    middleware: $middleware,
                    priority: $definition->priority,
                    regexPath: $resolved->regexPath,
                    arguments: $resolved->arguments,
                    stickyPrefix: $resolved->stickyPrefix,
                ),
            ];
        }

        $routes = [];
        $emittedMethods = [];

        foreach ($definition->methods as $requestMethod) {
            if (\in_array($requestMethod, $emittedMethods, true)) {
                continue;
            }

            $emittedMethods[] = $requestMethod;
            $routes[] = new Route(
                method: $requestMethod,
                path: $path,
                controller: $definition->controller,
                action: $definition->action,
                name: $name,
                middleware: $middleware,
                priority: $definition->priority,
                regexPath: $resolved->regexPath,
                arguments: $resolved->arguments,
                stickyPrefix: $resolved->stickyPrefix,
            );
        }

        return $routes;
    }

    /**
     * @param list<Method> $methods
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    private function addDefinition(
        array $methods,
        string $path,
        string $controller,
        string $action,
        ?string $name,
        array $middleware,
        RoutePriority $priority,
        ?string $prefix,
        bool $trailingSlash,
    ): static {
        $this->definitions[] = new RouteDefinition(
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
}
