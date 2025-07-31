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

use Tuxxedo\Collections\FileCollection;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\Attributes\Argument;
use Tuxxedo\Router\Attributes\Middleware;
use Tuxxedo\Router\Attributes\Route as RouteAttr;

// @todo Consider a verbosity mode for skipped routes
readonly class RouteDiscoverer
{
    /**
     * @var array<string, string>
     */
    private const array TYPE_PATTERNS = [
        'int' => '\d+',
        'alpha' => '[a-zA-Z]+',
        'slug' => '[a-z0-9-]+',
        'uuid' => '[0-9a-fA-F\-]{36}',
    ];

    public function __construct(
        public ContainerInterface $container,
        public string $baseNamespace,
        public string $directory,
    ) {
    }

    /**
     * @return \Generator<RouteInterface>
     */
    public function discover(): \Generator
    {
        $controllers = FileCollection::fromRecursiveFileType(
            directory: $this->directory,
            extension: 'php',
        );

        foreach ($controllers as $controller) {
            $reflector = new \ReflectionClass(
                $this->getControllerClassName($controller),
            );

            $baseMiddleware = $this->getMiddleware($reflector);
            $controllerAttribute = $this->getControllerAttribute($reflector);

            foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->isAbstract()) {
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

                    // @todo Consider changing this behavior to always be prefixed if $controllerAttribute is set
                    // @todo Change this to be nullable instead of an empty string
                    if ($uri === '') {
                        if ($controllerAttribute === null) {
                            continue;
                        }

                        if ($this->isIndexMethod($controllerAttribute, $method)) {
                            $uri = $controllerAttribute->uri;
                        } else {
                            $uri = $controllerAttribute->uri . $method->getName();
                        }
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
            $middleware[] = fn (): MiddlewareInterface => $this->container->resolve(
                $attribute->newInstance()->name,
            );
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
    private function getControllerAttribute(\ReflectionClass $reflector): ?Attributes\Controller
    {
        $attributes = $reflector->getAttributes(
            name: Attributes\Controller::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attributes) > 0) {
            return $attributes[0]->newInstance();
        }

        return null;
    }

    private function isIndexMethod(
        Attributes\Controller $controllerAttribute,
        \ReflectionMethod $method,
    ): bool {
        if ($controllerAttribute->autoIndex && $method->getName() === 'index') {
            return true;
        }

        $indexAttribute = $method->getAttributes(
            name: Attributes\Index::class,
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
    private function getUriArgumentNodes(
        string $uri,
    ): array {
        $nodes = [];
        $regex = \preg_match_all(
            pattern: '/\{([a-zA-Z_][a-zA-Z0-9_]*)(\?|:([^}]+)|<([^>]+)>)?}/',
            subject: $uri,
            matches: $matches,
            flags: \PREG_SET_ORDER,
        );

        if ($regex !== false && $regex > 0) {
            foreach ($matches as $match) {
                $label = $match[1];
                $modifier = $match[2] ?? null;

                $kind = ArgumentKind::TYPED_IMPLICIT;
                $constraint = null;

                if ($modifier === '?') {
                    $kind = ArgumentKind::OPTIONAL;
                } elseif (isset($match[3])) {
                    $kind = ArgumentKind::REGEX;
                    $constraint = $match[3];
                } elseif (isset($match[4])) {
                    $kind = ArgumentKind::TYPED_EXPLICIT;
                    $constraint = $match[4];
                }

                $nodes[] = new ArgumentNode(
                    label: $label,
                    kind: $kind,
                    constraint: $constraint,
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
        Attributes\Route $route,
    ): \Generator {
        $arguments = [];

        foreach ($nodes as $node) {
            $argument = match ($node->kind) {
                ArgumentKind::OPTIONAL => $this->discoverOptionalArgument(
                    node: $node,
                    method: $method,
                ),
                ArgumentKind::REGEX => $this->discoverRegexArgument(
                    node: $node,
                    method: $method,
                ),
                ArgumentKind::TYPED_EXPLICIT => $this->discoverExplicitlyTypedArgument(
                    node: $node,
                    method: $method,
                ),
                ArgumentKind::TYPED_IMPLICIT => $this->discoverImplicitlyTypedArgument(
                    node: $node,
                    method: $method,
                ),
            };

            if ($argument !== null) {
                $arguments[] = $argument;
            }
        }

        // @todo Check matching number of arguments to parameters
        // @todo Check for double labels

        if (\sizeof($route->methods) > 0) {
            foreach ($route->methods as $requestMethod) {
                yield new Route(
                    method: $requestMethod,
                    uri: $uri,
                    controller: $className,
                    action: $method->getName(),
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
            '/\/\{([a-zA-Z_][a-zA-Z0-9_]*)(\?|:([^}]+)|<([^>]+)>)?}/',
            static function (array $matches): string {
                $name = $matches[1];
                $isOptional = $matches[2] === '?';
                $type = $matches[3] ?? null;
                $custom = $matches[4] ?? null;
                $segment = '(?<' . $name . '>' . ($custom ?? (self::TYPE_PATTERNS[$type] ?? '[^/]+')) . ')';

                return $isOptional ? '(?:/' . $segment . ')?' : '/' . $segment;
            },
            $uri,
        ) . '/$#';
    }

    private function getNativeType(
        \ReflectionParameter $parameter,
    ): ?string {
        $type = $parameter->getType();

        if ($type === null) {
            return null;
        }

        if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
            if ($type->getName() === 'object' || $type->getName() === 'array') {
                return null;
            }

            return $type->getName();
        } elseif ($type->allowsNull()) {
            return 'null';
        }

        return null;
    }

    private function discoverOptionalArgument(
        ArgumentNode $node,
        \ReflectionMethod $method,
    ): ?RouteArgumentInterface {
        $parameter = $this->getNamedParameter($method, $node->label);

        if ($parameter === null || !$parameter->isDefaultValueAvailable()) {
            return null;
        }

        $nativeType = $this->getNativeType($parameter);

        if ($nativeType === null) {
            return null;
        }

        if ($parameter->getName() !== $node->label) {
            $mappedName = $parameter->getName();
        }

        return new OptionalRouteArgument(
            name: $node->label,
            mappedName: $mappedName ?? null,
            nativeType: $nativeType,
            defaultValue: $parameter->getDefaultValue(),
        );
    }

    private function discoverRegexArgument(
        ArgumentNode $node,
        \ReflectionMethod $method,
    ): null {
        // ??? RouteArgumentInterface
        return null;
    }

    private function discoverExplicitlyTypedArgument(
        ArgumentNode $node,
        \ReflectionMethod $method,
    ): null {
        // ??? RouteArgumentInterface
        return null;
    }

    private function discoverImplicitlyTypedArgument(
        ArgumentNode $node,
        \ReflectionMethod $method,
    ): null {
        // ??? RouteArgumentInterface
        return null;
    }
}
