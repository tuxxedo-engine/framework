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

namespace Tuxxedo\Router\Attributes;

use Tuxxedo\Http\Method;
use Tuxxedo\Router\RoutePriority;

abstract readonly class Route
{
    /**
     * @param Method[] $methods
     */
    public function __construct(
        public string $uri,
        public array $methods = [],
        public RoutePriority $priority = RoutePriority::NORMAL,
    ) {
    }
}
