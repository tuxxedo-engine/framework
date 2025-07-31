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

    public function getRoutes(): iterable
    {
        return $this->routes;
    }
}
