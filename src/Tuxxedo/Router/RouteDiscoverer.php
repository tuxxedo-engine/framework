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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\File\FileCollectionFactory;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\Attribute\Route as RouteAttr;
use Tuxxedo\Router\Compiler\PathCompiler;
use Tuxxedo\Router\Compiler\PathCompilerInterface;
use Tuxxedo\Router\Pattern\TypePatternRegistry;
use Tuxxedo\Router\Pattern\TypePatternRegistryInterface;

class RouteDiscoverer implements RouteDiscovererInterface
{
    public readonly TypePatternRegistryInterface $patterns;
    private readonly RouteArgumentResolverInterface $argumentResolver;
    private readonly RouteMiddlewareResolverInterface $middlewareResolver;
    private bool $hasDiscoveryRun = false;

    public function __construct(
        private readonly ContainerInterface $container,
        public readonly string $baseNamespace,
        public readonly string $directory,
        public readonly bool $strictMode = false,
        ?TypePatternRegistryInterface $patterns = null,
        ?PathCompilerInterface $pathCompiler = null,
        ?RouteArgumentResolverInterface $argumentResolver = null,
        ?RouteMiddlewareResolverInterface $middlewareResolver = null,
    ) {
        $this->patterns = $patterns ?? TypePatternRegistry::createDefault();
        $this->argumentResolver = $argumentResolver ?? new RouteArgumentResolver(
            pathCompiler: $pathCompiler ?? new PathCompiler(
                patterns: $this->patterns,
            ),
        );
        $this->middlewareResolver = $middlewareResolver ?? new RouteMiddlewareResolver(
            container: $this->container,
        );
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
        $controllers = FileCollectionFactory::paths(
            directory: $this->directory,
            pattern: '**/*.php',
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

            $baseMiddleware = $this->middlewareResolver->attributes($reflector);
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
                    $this->middlewareResolver->attributes($method),
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

        try {
            $resolved = $this->argumentResolver->resolve(
                path: $path,
                prefix: $prefix,
                method: $method,
            );
        } catch (RouterException $exception) {
            $this->handleError(
                static fn (): RouterException => $exception,
            );

            return;
        }

        if (\sizeof($route->methods) === 0) {
            yield new Route(
                method: null,
                path: $path,
                controller: $reflector->getName(),
                action: $method->getName(),
                name: $route->name,
                middleware: $middleware,
                priority: $route->priority,
                regexPath: $resolved->regexPath,
                arguments: $resolved->arguments,
                stickyPrefix: $resolved->stickyPrefix,
            );

            return;
        }

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
                regexPath: $resolved->regexPath,
                arguments: $resolved->arguments,
                stickyPrefix: $resolved->stickyPrefix,
            );
        }
    }

    /**
     * @return class-string
     */
    private function getControllerClassName(
        string $controllerFile,
    ): string {
        $resolvedDirectory = \realpath($this->directory);
        $normalizedDirectory = \str_replace(
            '\\',
            '/',
            $resolvedDirectory !== false ? $resolvedDirectory : $this->directory,
        );
        $controllerFile = \str_replace(
            [
                $normalizedDirectory . '/',
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
}
