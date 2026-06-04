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

namespace Unit\Router;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Method;
use Tuxxedo\Router\Route;

class RouteTest extends TestCase
{
    public function testRouteSupportsStringMethod(): void
    {
        $route = new Route(
            method: 'put',
            path: '/test',
            controller: static::class,
            action: __FUNCTION__,
        );

        self::assertSame(
            Method::PUT,
            $route->method,
        );
    }
}
