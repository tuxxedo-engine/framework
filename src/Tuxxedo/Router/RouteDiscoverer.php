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

use Tuxxedo\Collection\FileCollection;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\Attribute\Argument;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route as RouteAttr;
use Tuxxedo\Router\Pattern\TypePatternRegistry;
use Tuxxedo\Router\Pattern\TypePatternRegistryInterface;

// @todo This needs some handling for duplicated route names
class RouteDiscoverer implements RouteDiscovererInterface
{
    public readonly TypePatternRegistryInterface $patterns;

    /**
     * @var string[]
     */
    private array $namedRoutes = [];

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
    public function discover(): \Generator
    {
        // @todo Remove this hack once the discoverer supports better caching
        $this->namedRoutes = [];
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
                    $uri = $route->uri;

                    if ($route->name !== null) {
                        if ($this->isRouteNameInUse($route)) {
                            $this->handleError(
                                static fn (): RouterException => RouterException::fromDuplicateRouteName(
                                    className: $reflector->getName(),
                                    method: $method->getName(),
                                    name: $route->name,
                                ),
                            );

                            continue;
                        }

                        $this->namedRoutes[] = $route->name;
                    }


                    if ($controllerAttribute === null && $uri === null) {
                        $this->handleError(
                            static fn (): RouterException => RouterException::fromEmptyUri(
                                className: $reflector->getName(),
                                method: $method->getName(),
                            ),
                        );

                        continue;
                    } elseif ($uri === null) {
                        if ($this->isIndexMethod($controllerAttribute, $method)) {
                            $uri = $controllerAttribute->uri;
                        } else {
                            $uri = $controllerAttribute->uri . $method->getName();
                        }
                    } elseif ($controllerAttribute !== null) {
                        $uri = $controllerAttribute->uri . $uri;
                    }

                    $argumentNodes = $this->getUriArgumentNodes($uri);

                    if (\sizeof($argumentNodes) > 0) {
                        yield from $this->discoverRoutesWithArguments(
                            uri: $uri,
                            middleware: $middleware,
                            nodes: $argumentNodes,
                            className: $reflector->getName(),
                            method: $method,
                            route: $route,
                        );
                    } elseif (\sizeof($route->methods) > 0) {
                        foreach ($route->methods as $requestMethod) {
                            yield new Route(
                                method: $requestMethod,
                                uri: $uri,
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
                            uri: $uri,
                            controller: $reflector->getName(),
                            action: $method->getName(),
                            name: $route->name,
                            middleware: $middleware,
                            priority: $route->priority,
                        );
                    }
                }
            }
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

        foreach ($reflector->getAttributes(Middleware::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $middlewareInstance = $attribute->newInstance()->middleware;

            if (\is_string($middlewareInstance)) {
                $middleware[] = fn (): MiddlewareInterface => /** @var MiddlewareInterface */
                    $this->container->resolve($middlewareInstance)
                ;
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
    ): bool {
        if (
            $controllerAttribute->autoIndex &&
            $method->getName() === $controllerAttribute->autoIndexMethodName
        ) {
            return true;
        }

        $indexAttribute = $method->getAttributes(
            name: Attribute\Index::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($indexAttribute) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @return ArgumentNode[]
     */
    private function getUriArgumentNodes(string $uri): array
    {
        $nodes = [];
        $regex = \preg_match_all(
            '/\{(\??)([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+)|<([^>]+)>)?}/',
            $uri,
            $matches,
            \PREG_SET_ORDER,
        );

        if ($regex !== false && $regex > 0) {
            foreach ($matches as $match) {
                $regexConstraint = $match[3] ?? null;
                $typeConstraint = $match[4] ?? null;
                $constraint = $regexConstraint ?? $typeConstraint;
                $kind = ArgumentKind::TYPED_IMPLICIT;

                if ($regexConstraint !== null) {
                    $kind = ArgumentKind::REGEX;
                } elseif ($typeConstraint !== null) {
                    $kind = ArgumentKind::TYPED_EXPLICIT;
                }

                $nodes[] = new ArgumentNode(
                    name: $match[2],
                    kind: $kind,
                    constraint: $constraint,
                    optional: $match[1] === '?',
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
        string $uri,
        array $middleware,
        array $nodes,
        string $className,
        \ReflectionMethod $method,
        RouteAttr $route,
    ): \Generator {
        $arguments = [];

        foreach ($nodes as $node) {
            $argument = $this->getRouteArgument(
                node: $node,
                method: $method,
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
            foreach ($route->methods as $requestMethod) {
                yield new Route(
                    method: $requestMethod,
                    uri: $uri,
                    controller: $className,
                    action: $method->getName(),
                    name: $route->name,
                    middleware: $middleware,
                    priority: $route->priority,
                    regexUri: $this->getRegexUri($uri),
                    arguments: $arguments,
                );
            }
        } else {
            yield new Route(
                method: null,
                uri: $uri,
                controller: $className,
                action: $method->getName(),
                name: $route->name,
                middleware: $middleware,
                priority: $route->priority,
                regexUri: $this->getRegexUri($uri),
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
                $this->handleError(
                    static fn (): RouterException => RouterException::fromNoArgumentAttributeFound(
                        className: $method->getDeclaringClass()->getName(),
                        method: $method->getName(),
                        parameter: $parameter->getName(),
                    ),
                );

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

    private function getRegexUri(string $uri): string
    {
        return '#^' . \preg_replace_callback(
            '/\/\{(\??)([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+)|<([^>]+)>)?}/',
            function (array $matches): string {
                $regex = $matches[3] ?? null;
                $type = $matches[4] ?? null;

                if ($regex === '') {
                    $regex = null;
                }

                $pattern = $regex ?? $this->patterns->get($type ?? '')->regex ?? '[^/]+';
                $segment = '(?<' . $matches[2] . '>' . $pattern . ')';

                return $matches[1] === '?'
                    ? '(?:/' . $segment . ')?'
                    : '/' . $segment;
            },
            $uri,
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
    ): ?RouteArgumentInterface {
        $parameter = $this->getNamedParameter($method, $node->name);

        if ($parameter === null) {
            return null;
        }

        if ($node->optional && !$parameter->isDefaultValueAvailable()) {
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
                : null,
        );
    }

    private function isRouteNameInUse(
        RouteAttr $route,
    ): bool {
        return \in_array($route->name, $this->namedRoutes, true);
    }
}
