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
use Tuxxedo\Router\Builder\RouteBuilder;
use Tuxxedo\Router\Compiler\PathCompilerInterface;

class StaticRouter extends AbstractRouter
{
    /**
     * @param RouteInterface[] $routes
     */
    final public function __construct(
        public readonly array $routes,
    ) {
    }

    /**
     * @param RouteInterface[] $routes
     */
    public static function createPriorityBased(
        array $routes,
    ): static {
        \uasort(
            $routes,
            static fn (RouteInterface $a, RouteInterface $b): int => $a->priority->value <=> $b->priority->value,
        );

        return new static($routes);
    }

    /**
     * @param list<string> $files
     *
     * @throws RouterException
     */
    public static function createFromRouteFiles(
        ContainerInterface $container,
        array $files,
        ?PathCompilerInterface $pathCompiler = null,
    ): static {
        $routes = [];

        foreach ($files as $file) {
            if (!\is_file($file)) {
                throw RouterException::fromRouteFileNotFound(
                    path: $file,
                );
            }

            $factory = require $file;

            if (!$factory instanceof \Closure) {
                throw RouterException::fromRouteFileInvalidReturn(
                    path: $file,
                );
            }

            $builder = new RouteBuilder(
                container: $container,
                pathCompiler: $pathCompiler,
            );

            $emitted = $factory($builder);

            if (!\is_array($emitted)) {
                throw RouterException::fromRouteFileInvalidReturn(
                    path: $file,
                );
            }

            foreach ($emitted as $route) {
                if (!$route instanceof RouteInterface) {
                    throw RouterException::fromRouteFileInvalidReturn(
                        path: $file,
                    );
                }

                $routes[] = $route;
            }
        }

        return new static($routes);
    }

    public function getRoutes(): iterable
    {
        return $this->routes;
    }
}
