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

namespace Support\Http\Router;

use Tuxxedo\Router\DispatchableRouteInterface;
use Tuxxedo\Router\RouteInterface;

class StubDispatchableRoute implements DispatchableRouteInterface
{
    public RouteInterface $route {
        get {
            throw new \LogicException('Not implemented in stub');
        }
    }

    public array $arguments {
        get {
            return [];
        }
    }

    public function asUrl(): ?string
    {
        return null;
    }
}
