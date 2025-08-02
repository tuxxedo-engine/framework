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
        public readonly ContainerInterface $container,
        public readonly string $directory,
        public readonly string $baseNamespace,
        public readonly bool $strictMode = false,
    ) {
    }

    public function getRoutes(): iterable
    {
        yield from (new RouteDiscoverer(
            container: $this->container,
            baseNamespace: $this->baseNamespace,
            directory: $this->directory,
            strictMode: $this->strictMode,
        ))->discover();
    }
}
