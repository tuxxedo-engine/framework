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

use Tuxxedo\Router\Pattern\TypePatternRegistryInterface;

interface RouteDiscovererInterface
{
    public TypePatternRegistryInterface $patterns {
        get;
    }

    public string $baseNamespace {
        get;
    }

    public string $directory {
        get;
    }

    public bool $strictMode {
        get;
    }

    /**
     * @return \Generator<RouteInterface>
     */
    public function discover(): \Generator;
}
