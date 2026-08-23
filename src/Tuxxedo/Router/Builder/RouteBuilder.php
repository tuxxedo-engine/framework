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
use Tuxxedo\Router\ArgumentNode;
use Tuxxedo\Router\Attribute\Argument;
use Tuxxedo\Router\Compiler\PathCompiler;
use Tuxxedo\Router\Compiler\PathCompilerInterface;
use Tuxxedo\Router\Pattern\TypePatternRegistry;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\RouteArgument;
use Tuxxedo\Router\RouteArgumentInterface;
use Tuxxedo\Router\RoutePriority;
use Tuxxedo\Router\RouterException;

class RouteBuilder implements RouteBuilderInterface
{
    /**
     * @var list<RouteDefinition>
     */
    private array $definitions = [];

    private readonly PathCompilerInterface $pathCompiler;

    public function __construct(
        private readonly ContainerInterface $container,
        ?PathCompilerInterface $pathCompiler = null,
    ) {
        $this->pathCompiler = $pathCompiler ?? new PathCompiler(
            patterns: TypePatternRegistry::createDefault(),
        );
    }

    public function get(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::GET,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function post(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::POST,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function put(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::PUT,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function patch(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::PATCH,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function delete(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::DELETE,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function options(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::OPTIONS,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function head(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::HEAD,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function connect(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::CONNECT,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function trace(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::TRACE,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function query(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: Method::QUERY,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function any(
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        array $middleware = [],
        RoutePriority $priority = RoutePriority::NORMAL,
    ): static {
        return $this->addDefinition(
            method: null,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );
    }

    public function group(
        string $uri,
        array $middleware,
        \Closure $callback,
    ): static {
        $group = new RouteBuilderGroup(
            sink: function (RouteDefinition $definition): void {
                $this->definitions[] = $definition;
            },
            prefix: $uri,
            middleware: $middleware,
        );

        $callback($group);

        return $this;
    }

    public function build(): array
    {
        $routes = [];

        foreach ($this->definitions as $definition) {
            $compiled = $this->pathCompiler->compile(
                path: $definition->uri,
            );

            $arguments = $this->resolveArguments(
                controller: $definition->controller,
                action: $definition->action,
                nodes: $compiled->argumentNodes,
            );

            $routes[] = new Route(
                method: $definition->method,
                path: $definition->uri,
                controller: $definition->controller,
                action: $definition->action,
                name: $definition->name,
                middleware: $this->normalizeMiddleware(
                    middleware: $definition->middleware,
                ),
                priority: $definition->priority,
                regexPath: $compiled->regexPath,
                arguments: $arguments,
            );
        }

        return $routes;
    }

    /**
     * @param class-string $controller
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     */
    private function addDefinition(
        ?Method $method,
        string $uri,
        string $controller,
        string $action,
        ?string $name,
        array $middleware,
        RoutePriority $priority,
    ): static {
        $this->definitions[] = new RouteDefinition(
            method: $method,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            middleware: $middleware,
            priority: $priority,
        );

        return $this;
    }

    /**
     * @param class-string $controller
     * @param list<ArgumentNode> $nodes
     * @return list<RouteArgumentInterface>
     *
     * @throws RouterException
     */
    private function resolveArguments(
        string $controller,
        string $action,
        array $nodes,
    ): array {
        if (\sizeof($nodes) === 0) {
            return [];
        }

        $names = \array_map(
            static fn (ArgumentNode $node): string => $node->name,
            $nodes,
        );

        if (\sizeof($names) !== \sizeof(\array_unique($names))) {
            throw RouterException::fromNotAllArgumentNamesAreUnique(
                className: $controller,
                method: $action,
                names: $names,
            );
        }

        $reflection = new \ReflectionMethod(
            objectOrMethod: $controller,
            method: $action,
        );

        $arguments = [];

        foreach ($nodes as $node) {
            $arguments[] = $this->resolveArgument(
                node: $node,
                method: $reflection,
            );
        }

        return $arguments;
    }

    /**
     * @throws RouterException
     */
    private function resolveArgument(
        ArgumentNode $node,
        \ReflectionMethod $method,
    ): RouteArgumentInterface {
        $parameter = self::findNamedParameter(
            method: $method,
            name: $node->name,
        );

        if ($parameter === null) {
            throw RouterException::fromArgumentHasNoMatchingParameter(
                className: $method->getDeclaringClass()->getName(),
                method: $method->getName(),
                argument: $node->name,
            );
        }

        if (
            $node->optional &&
            !$parameter->isDefaultValueAvailable()
        ) {
            throw RouterException::fromOptionalArgumentHasNoDefaultValue(
                className: $method->getDeclaringClass()->getName(),
                method: $method->getName(),
                parameter: $parameter->getName(),
            );
        }

        $allowsNull = false;
        $nativeType = $this->extractNativeType(
            method: $method,
            parameter: $parameter,
            allowsNull: $allowsNull,
        );

        $mappedName = $parameter->getName() !== $node->name
            ? $parameter->getName()
            : null;

        return new RouteArgument(
            node: $node,
            mappedName: $mappedName,
            nativeType: $nativeType,
            allowsNull: $allowsNull,
            defaultValue: $parameter->isDefaultValueAvailable()
                ? $parameter->getDefaultValue()
                : null,
        );
    }

    private static function findNamedParameter(
        \ReflectionMethod $method,
        string $name,
    ): ?\ReflectionParameter {
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }

            $parameterAttributes = $parameter->getAttributes(
                name: Argument::class,
                flags: \ReflectionAttribute::IS_INSTANCEOF,
            );

            if (\sizeof($parameterAttributes) === 0) {
                continue;
            }

            /** @var Argument $instance */
            $instance = $parameterAttributes[0]->newInstance();

            if ($instance->label === $name) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * @throws RouterException
     */
    private function extractNativeType(
        \ReflectionMethod $method,
        \ReflectionParameter $parameter,
        bool &$allowsNull,
    ): string {
        $type = $parameter->getType();

        if ($type === null) {
            throw RouterException::fromHasNoType(
                className: $method->getDeclaringClass()->getName(),
                method: $method->getName(),
                parameter: $parameter->getName(),
            );
        }

        if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
            if ($type->getName() === 'object' || $type->getName() === 'array') {
                throw RouterException::fromUnsupportedNativeType(
                    className: $method->getDeclaringClass()->getName(),
                    method: $method->getName(),
                    parameter: $parameter->getName(),
                    type: $type->getName(),
                );
            }

            $allowsNull = $type->allowsNull();

            return $type->getName();
        }

        if ($type->allowsNull()) {
            return 'null';
        }

        throw RouterException::fromUnsupportedType(
            className: $method->getDeclaringClass()->getName(),
            method: $method->getName(),
            parameter: $parameter->getName(),
        );
    }

    /**
     * @param list<class-string<MiddlewareInterface>|\Closure(): MiddlewareInterface> $middleware
     * @return list<\Closure(): MiddlewareInterface>
     */
    private function normalizeMiddleware(
        array $middleware,
    ): array {
        $normalized = [];
        $container = $this->container;

        foreach ($middleware as $entry) {
            if ($entry instanceof \Closure) {
                $normalized[] = $entry;

                continue;
            }

            $className = $entry;
            $normalized[] = static fn (): MiddlewareInterface => /** @var MiddlewareInterface */ $container->resolve($className);
        }

        return $normalized;
    }
}
