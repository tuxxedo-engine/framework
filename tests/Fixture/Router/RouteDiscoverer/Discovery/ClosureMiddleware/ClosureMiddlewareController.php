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

namespace Fixture\Router\RouteDiscoverer\Discovery\ClosureMiddleware;

use Fixture\Router\RouteDiscoverer\Support\TestMiddleware;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route\Get;

class ClosureMiddlewareController
{
    #[Get(uri: '/closure')]
    #[Middleware(
        static function (ContainerInterface $container): MiddlewareInterface {
            return new TestMiddleware();
        },
    )]
    public function action(): void
    {
    }
}
