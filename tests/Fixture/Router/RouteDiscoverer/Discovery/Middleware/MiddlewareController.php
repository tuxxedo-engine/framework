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

namespace Fixture\Router\RouteDiscoverer\Discovery\Middleware;

use Fixture\Router\RouteDiscoverer\Support\AnotherMiddleware;
use Fixture\Router\RouteDiscoverer\Support\TestMiddleware;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route\Get;

#[Middleware(middleware: TestMiddleware::class)]
class MiddlewareController
{
    #[Get(path: '/protected')]
    #[Middleware(middleware: AnotherMiddleware::class)]
    public function protected(): void
    {
    }

    #[Get(path: '/open')]
    public function open(): void
    {
    }
}
