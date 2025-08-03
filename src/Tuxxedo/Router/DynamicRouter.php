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
    private function __construct(
        public readonly RouteDiscovererInterface $discoverer,
    ) {
    }

    public static function createFromDirectory(
        ContainerInterface $container,
        string $directory,
        string $baseNamespace,
        bool $strictMode = false,
    ): self {
        return new self(
            discoverer: new RouteDiscoverer(
                container: $container,
                baseNamespace: $baseNamespace,
                directory: $directory,
                strictMode: $strictMode,
            ),
        );
    }

    public static function createFromDiscoverer(
        RouteDiscovererInterface $discoverer,
    ): self {
        return new self(
            discoverer: $discoverer,
        );
    }

    public function getRoutes(): iterable
    {
        yield from $this->discoverer->discover();
    }
}
