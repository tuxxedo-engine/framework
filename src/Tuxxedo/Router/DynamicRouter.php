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
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\Attributes\Middleware;
use Tuxxedo\Router\Attributes\Route as RouteAttr;

// @todo Consider a verbosity mode for skipped routes
class DynamicRouter extends StaticRouter
{
    /**
     * @throws HttpException
     */
    public function __construct(
        private readonly ContainerInterface $container,
        string $directory,
        string $baseNamespace,
    ) {
        parent::__construct(
            routes: $this->getRoutes(
                baseNamespace: $baseNamespace,
                directory: $directory,
            ),
        );
    }

    /**
     * @return RouteInterface[]
     *
     * @throws HttpException
     */
    private function getRoutes(
        string $baseNamespace,
        string $directory,
    ): array {
        $controllers = FileCollection::fromRecursiveFileType(
            directory: $directory,
            extension: 'php',
        );

        $routes = [];

        foreach ($controllers as $controller) {
            $reflector = new \ReflectionClass(
                $this->getControllerClassName(
                    baseDirectory: $directory,
                    baseNamespace: $baseNamespace,
                    controllerFile: $controller,
                ),
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

                    if ($uri === '') {
                        if ($controllerAttribute === null) {
                            continue;
                        }

                        // @todo Make alias?
                        if ($method->getName() === 'index') {
                            $uri = $controllerAttribute->uri;
                        } else {
                            $uri = $controllerAttribute->uri . $method->getName();
                        }
                    }

                    if (\sizeof($route->methods) > 0) {
                        foreach ($route->methods as $requestMethod) {
                            $routes[] = new Route(
                                method: $requestMethod,
                                uri: $uri,
                                controller: $reflector->getName(),
                                action: $method->getName(),
                                middleware: $middleware,
                                priority: $route->priority,
                            );
                        }
                    } else {
                        $routes[] = new Route(
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

        return $routes;
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
     *
     * @throws HttpException
     */
    private function getControllerClassName(
        string $baseDirectory,
        string $baseNamespace,
        string $controllerFile,
    ): string {
        $controllerFile = \str_replace(
            [
                $baseDirectory . '/',
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
        return $baseNamespace . $controllerFile;
    }

    /**
     * @param \ReflectionClass<object> $reflector
     */
    private function getControllerAttribute(\ReflectionClass $reflector): ?RouteAttr\Controller
    {
        $attributes = $reflector->getAttributes(
            name: RouteAttr\Controller::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attributes) > 0) {
            return $attributes[0]->newInstance();
        }

        return null;
    }
}
