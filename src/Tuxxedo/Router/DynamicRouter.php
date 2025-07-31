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

use Tuxxedo\Container\ContainerInterface;

class DynamicRouter extends AbstractRouter
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $directory,
        private readonly string $baseNamespace,
    ) {
    }

    public function getRoutes(): iterable
    {
        yield from (new RouteDiscoverer(
            container: $this->container,
            baseNamespace: $this->baseNamespace,
            directory: $this->directory,
        ))->discover();
    }
}
