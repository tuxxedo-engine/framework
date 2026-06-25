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

namespace Tuxxedo\Router;

use Tuxxedo\Collection\FileCollection;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\Attribute\Argument;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route as RouteAttr;
use Tuxxedo\Router\Pattern\TypePatternRegistry;
use Tuxxedo\Router\Pattern\TypePatternRegistryInterface;

class RouteDiscoverer implements RouteDiscovererInterface
{
    public readonly TypePatternRegistryInterface $patterns;
    private bool $hasDiscoveryRun = false;

    public function __construct(
        private readonly ContainerInterface $container,
        public readonly string $baseNamespace,
        public readonly string $directory,
        public readonly bool $strictMode = false,
        ?TypePatternRegistryInterface $patterns = null,
    ) {
        $this->patterns = $patterns ?? TypePatternRegistry::createDefault();
    }

    /**
     * @param \Closure(): RouterException $e
     *
     * @throws RouterException
     */
    private function handleError(
        \Closure $e,
    ): void {
        if ($this->strictMode) {
            throw $e();
        }
    }

    /**
     * @return \Generator<RouteInterface>
     */
    public function discover(
        bool $rediscover = false,
    ): \Generator {
        if (!$rediscover && $this->hasDiscoveryRun) {
            return;
        }

        $namedRoutes = [];
        $controllers = FileCollection::fromRecursiveFileType(
            directory: $this->directory,
            extension: '.php',
        );

        foreach ($controllers as $controller) {
            $reflector = new \ReflectionClass(
                $this->getControllerClassName($controller),
            );

            if (
                $reflector->isEnum() ||
                $reflector->isAbstract() ||
                $reflector->isInterface() ||
                $reflector->isTrait()
            ) {
                $this->handleError(
                    static fn (): RouterException => RouterException::fromInvalidClassLikeStructure(
                        className: $reflector->getName(),
                    ),
                );

                continue;
            }

            $baseMiddleware = $this->getMiddleware($reflector);
            $controllerAttribute = $this->getControllerAttribute($reflector);

            foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->isAbstract()) {
                    $this->handleError(
                        static fn (): RouterException => RouterException::fromNonInstantiableMethod(
                            className: $reflector->getName(),
                            method: $method->getName(),
                        ),
                    );

                    continue;
                }

                $middleware = \array_merge(
                    $baseMiddleware,
                    $this->getMiddleware($method),
                );

                foreach ($method->getAttributes(RouteAttr::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                    /** @var RouteAttr $route */
                    $route = $attribute->newInstance();
                    $prefix = null;
                    $path = $route->path;
                    $indexAttribute = null;

                    if ($controllerAttribute === null && $path === null) {
                        $this->handleError(
                            static fn (): RouterException => RouterException::fromEmptyPath(
                                className: $reflector->getName(),
                                method: $method->getName(),
                            ),
                        );

                        return;
                    } elseif ($path === null) {
                        if ($this->isIndexMethod($controllerAttribute, $method, $indexAttribute)) {
                            $path = $controllerAttribute->path;
                        } else {
                            $path = $controllerAttribute->path . $method->getName();
                        }

                        $prefix = $controllerAttribute->prefix;
                    } elseif ($controllerAttribute !== null) {
                        $path = $controllerAttribute->path . $path;
                        $prefix = $controllerAttribute->prefix;
                    }

                    if ($route->prefix !== null) {
                        $prefix = $route->prefix;
                    }

                    if ($prefix !== null) {
                        $prefix = \is_a($prefix, PrefixInterface::class, true)
                            ? $this->container->resolve($prefix)
                            : new Prefix($prefix);

                        $path = $prefix->path . $path;
                    }

                    $effectiveRoute = ($indexAttribute?->name !== null && $route->name === null)
                        ? $route->withName($indexAttribute->name)
                        : $route;

                    yield from $this->emitRoutes(
                        route: $effectiveRoute,
                        prefix: $prefix,
                        path: $path,
                        middleware: $middleware,
                        reflector: $reflector,
                        method: $method,
                        namedRoutes: $namedRoutes,
                    );

                    if ($indexAttribute?->name !== null && $route->name !== null) {
                        yield from $this->emitRoutes(
                            route: $route->withName($indexAttribute->name),
                            prefix: $prefix,
                            path: $path,
                            middleware: $middleware,
                            reflector: $reflector,
                            method: $method,
                            namedRoutes: $namedRoutes,
                        );
                    }

                    if (
                        ($route->trailingSlash || ($controllerAttribute !== null && $controllerAttribute->autoTrailingSlash)) &&
                        !\str_ends_with($path, '/')
                    ) {
                        yield from $this->emitRoutes(
                            route: $route->withPath($path . '/'),
                            prefix: $prefix,
                            path: $path . '/',
                            middleware: $middleware,
                            reflector: $reflector,
                            method: $method,
                            namedRoutes: $namedRoutes,
                        );
                    }
                }
            }
        }

        $this->hasDiscoveryRun = true;
    }

    /**
     * @param array<\Closure(): MiddlewareInterface> $middleware
     * @param \ReflectionClass<object> $reflector
     * @param string[] $namedRoutes
     * @return \Generator<RouteInterface>
     */
    private function emitRoutes(
        RouteAttr $route,
        ?PrefixInterface $prefix,
        string $path,
        array $middleware,
        \ReflectionClass $reflector,
        \ReflectionMethod $method,
        array &$namedRoutes,
    ): \Generator {
        if ($route->name !== null) {
            if (\in_array($route->name, $namedRoutes, true)) {
                $this->handleError(
                    static fn (): RouterException => RouterException::fromDuplicateRouteName(
                        className: $reflector->getName(),
                        method: $method->getName(),
                        name: $route->name,
                    ),
                );

                return;
            }

            $namedRoutes[] = $route->name;
        }

        $argumentNodes = $this->getPathArgumentNodes($path, $prefix);

        if (\sizeof($argumentNodes) > 0) {
            yield from $this->discoverRoutesWithArguments(
                path: $path,
                middleware: $middleware,
                nodes: $argumentNodes,
                className: $reflector->getName(),
                method: $method,
                route: $route,
                prefix: $prefix,
            );
        } elseif (\sizeof($route->methods) > 0) {
            $emittedMethods = [];

            foreach ($route->methods as $requestMethod) {
                if (\in_array($requestMethod, $emittedMethods, true)) {
                    continue;
                }

                $emittedMethods[] = $requestMethod;

                yield new Route(
                    method: $requestMethod,
                    path: $path,
                    controller: $reflector->getName(),
                    action: $method->getName(),
                    name: $route->name,
                    middleware: $middleware,
                    priority: $route->priority,
                );
            }
        } else {
            yield new Route(
                method: null,
                path: $path,
                controller: $reflector->getName(),
                action: $method->getName(),
                name: $route->name,
                middleware: $middleware,
                priority: $route->priority,
            );
        }
    }

    /**
     * @param \ReflectionClass<object>|\ReflectionMethod $reflector
     * @return array<\Closure(): MiddlewareInterface>
     */
    private function getMiddleware(
        \ReflectionClass|\ReflectionMethod $reflector,
    ): array {
        $middleware = [];

        /** @var \ReflectionAttribute<Middleware> $attribute */
        foreach ($reflector->getAttributes(Middleware::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $middlewareInstance = $attribute->newInstance()->middleware;

            if (\is_string($middlewareInstance)) {
                $middleware[] = fn (): MiddlewareInterface => /** @var MiddlewareInterface */ $this->container->resolve($middlewareInstance);
            } else {
                $middleware[] = fn (): MiddlewareInterface => $middlewareInstance($this->container);
            }
        }

        return $middleware;
    }

    /**
     * @return class-string
     */
    private function getControllerClassName(
        string $controllerFile,
    ): string {
        $controllerFile = \str_replace(
            [
                $this->directory . '/',
                '.php',
                '/',
            ],
            [
                '',
                '',
                '\\',
            ],
            $controllerFile,
        );

        /** @var class-string */
        return $this->baseNamespace . $controllerFile;
    }

    /**
     * @param \ReflectionClass<object> $reflector
     */
    private function getControllerAttribute(\ReflectionClass $reflector): ?Attribute\Controller
    {
        $attributes = $reflector->getAttributes(
            name: Attribute\Controller::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attributes) > 0) {
            /** @var Attribute\Controller */
            return $attributes[0]->newInstance();
        }

        return null;
    }

    private function isIndexMethod(
        Attribute\Controller $controllerAttribute,
        \ReflectionMethod $method,
        ?Attribute\Index &$indexAttribute = null,
    ): bool {
        if (
            $controllerAttribute->autoIndex &&
            $method->getName() === $controllerAttribute->autoIndexMethodName
        ) {
            return true;
        }

        $attributes = $method->getAttributes(
            name: Attribute\Index::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attributes) > 0) {
            /** @var Attribute\Index */
            $indexAttribute = $attributes[0]->newInstance();

            return true;
        }

        return false;
    }

    /**
     * @return ArgumentNode[]
     */
    private function getPathArgumentNodes(
        string $path,
        ?PrefixInterface $prefix,
    ): array {
        $nodes = [];
        $prefixedArguments = [];

        if ($prefix !== null) {
            $regex = \preg_match_all(
                '/\{(\??)([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+)|<([^>]+)>)?}/',
                $prefix->path,
                $matches,
                \PREG_SET_ORDER,
            );

            if ($regex !== false && $regex > 0) {
                foreach ($matches as $match) {
                    $prefixedArguments[] = $match[2];
                }
            }
        }

        $regex = \preg_match_all(
            '/\{(\??)([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+)|<([^>]+)>)?}/',
            $path,
            $matches,
            \PREG_SET_ORDER,
        );

        if ($regex !== false && $regex > 0) {
            foreach ($matches as $match) {
                $regexConstraint = $match[3] ?? null;
                $typeConstraint = $match[4] ?? null;
                $constraint = null;
                $kind = ArgumentKind::TYPED_IMPLICIT;

                if ($typeConstraint !== null) {
                    $kind = ArgumentKind::TYPED_EXPLICIT;
                    $constraint = $typeConstraint;
                } elseif ($regexConstraint !== null) {
                    $kind = ArgumentKind::REGEX;
                    $constraint = $regexConstraint;
                }

                $nodes[] = new ArgumentNode(
                    name: $match[2],
                    kind: $kind,
                    constraint: $constraint,
                    optional: $match[1] === '?',
                    prefixed: \in_array($match[2], $prefixedArguments, true),
                );
            }
        }

        return $nodes;
    }

    /**
     * @param array<(\Closure(): MiddlewareInterface)> $middleware
     * @param ArgumentNode[] $nodes
     * @param class-string $className
     * @return \Generator<RouteInterface>
     */
    private function discoverRoutesWithArguments(
        string $path,
        array $middleware,
        array $nodes,
        string $className,
        \ReflectionMethod $method,
        RouteAttr $route,
        ?PrefixInterface $prefix = null,
    ): \Generator {
        $arguments = [];

        foreach ($nodes as $node) {
            $argument = $this->getRouteArgument(
                node: $node,
                method: $method,
                prefix: $prefix,
            );

            if ($argument === null) {
                return;
            }

            $arguments[] = $argument;
        }

        $names = \array_map(
            static fn (ArgumentNode $node): string => $node->name,
            $nodes,
        );

        if (\sizeof($names) !== \sizeof(\array_unique($names))) {
            $this->handleError(
                static fn (): RouterException => RouterException::fromNotAllArgumentNamesAreUnique(
                    className: $className,
                    method: $method->getName(),
                    names: $names,
                ),
            );

            return;
        }

        if (\sizeof($route->methods) > 0) {
            $emittedMethods = [];

            foreach ($route->methods as $requestMethod) {
                if (\in_array($requestMethod, $emittedMethods, true)) {
                    continue;
                }

                $emittedMethods[] = $requestMethod;

                yield new Route(
                    method: $requestMethod,
                    path: $path,
                    controller: $className,
                    action: $method->getName(),
                    name: $route->name,
                    middleware: $middleware,
                    priority: $route->priority,
                    regexPath: $this->getRegexPath($path),
                    arguments: $arguments,
                );
            }
        } else {
            yield new Route(
                method: null,
                path: $path,
                controller: $className,
                action: $method->getName(),
                name: $route->name,
                middleware: $middleware,
                priority: $route->priority,
                regexPath: $this->getRegexPath($path),
                arguments: $arguments,
            );
        }
    }

    private function getNamedParameter(
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

    private function getRegexPath(string $path): string
    {
        return '#^' . \preg_replace_callback(
            '/(\/?)\{(\??)([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+)|<([^>]+)>)?}/',
            function (array $matches): string {
                $regex = ($matches[4] ?? '') !== '' ? $matches[4] : null;
                $type = ($matches[5] ?? '') !== '' ? $matches[5] : null;

                $pattern = $regex ?? $this->patterns->get($type ?? '')->regex ?? '[^/]+';
                $segment = '(?<' . $matches[3] . '>' . $pattern . ')';

                return $matches[2] === '?'
                    ? '(?:' . $matches[1] . $segment . ')?'
                    : $matches[1] . $segment;
            },
            $path,
        ) . '$#';
    }

    private function getNativeType(
        \ReflectionMethod $method,
        \ReflectionParameter $parameter,
        bool &$allowsNull = false,
    ): ?string {
        $type = $parameter->getType();

        if ($type === null) {
            $this->handleError(
                static fn (): RouterException => RouterException::fromHasNoType(
                    className: $method->getDeclaringClass()->getName(),
                    method: $method->getName(),
                    parameter: $parameter->getName(),
                ),
            );

            return null;
        }

        if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
            if ($type->getName() === 'object' || $type->getName() === 'array') {
                $this->handleError(
                    static fn (): RouterException => RouterException::fromUnsupportedNativeType(
                        className: $method->getDeclaringClass()->getName(),
                        method: $method->getName(),
                        parameter: $parameter->getName(),
                        type: $type->getName(),
                    ),
                );

                return null;
            }

            $allowsNull = $type->allowsNull();

            return $type->getName();
        } elseif ($type->allowsNull()) {
            return 'null';
        }

        $this->handleError(
            static fn (): RouterException => RouterException::fromUnsupportedType(
                className: $method->getDeclaringClass()->getName(),
                method: $method->getName(),
                parameter: $parameter->getName(),
            ),
        );

        return null;
    }

    private function getRouteArgument(
        ArgumentNode $node,
        \ReflectionMethod $method,
        ?PrefixInterface $prefix = null,
    ): ?RouteArgumentInterface {
        $parameter = $this->getNamedParameter($method, $node->name);

        if ($parameter === null) {
            if (
                $node->prefixed &&
                $prefix instanceof PrefixDefaultsInterface
            ) {
                $defaultValue = $prefix->getDefaultValue($node->name);

                return new RouteArgument(
                    node: $node,
                    mappedName: null,
                    nativeType: \get_debug_type($defaultValue),
                    allowsNull: $defaultValue === null,
                    defaultValue: $defaultValue,
                );
            }

            return null;
        }

        if (
            $node->optional &&
            !$parameter->isDefaultValueAvailable() &&
            !($node->prefixed && $prefix instanceof PrefixDefaultsInterface)
        ) {
            $this->handleError(
                static fn (): RouterException => RouterException::fromOptionalArgumentHasNoDefaultValue(
                    className: $method->getDeclaringClass()->getName(),
                    method: $method->getName(),
                    parameter: $parameter->getName(),
                ),
            );

            return null;
        }

        $allowsNull = false;
        $nativeType = $this->getNativeType(
            method: $method,
            parameter: $parameter,
            allowsNull: $allowsNull,
        );

        if ($nativeType === null) {
            return null;
        }

        if ($parameter->getName() !== $node->name) {
            $mappedName = $parameter->getName();
        }

        return new RouteArgument(
            node: $node,
            mappedName: $mappedName ?? null,
            nativeType: $nativeType,
            allowsNull: $allowsNull,
            defaultValue: $parameter->isDefaultValueAvailable()
                ? $parameter->getDefaultValue()
                : (
                    $node->prefixed && $prefix instanceof PrefixDefaultsInterface
                        ? $prefix->getDefaultValue($node->name)
                        : null
                ),
        );
    }
}
