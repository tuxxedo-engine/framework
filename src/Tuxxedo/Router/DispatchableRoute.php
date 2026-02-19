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

readonly class DispatchableRoute implements DispatchableRouteInterface
{
    /**
     * @param string[] $arguments
     */
    public function __construct(
        public RouteInterface $route,
        public array $arguments = [],
    ) {
    }

    public function asUrl(): string
    {
        // @todo Implement URL generation
        return $this->route->uri;
    }
}
