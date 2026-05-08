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

namespace Fixture\Router\RouteDiscoverer\Support;

use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class AnotherMiddleware implements MiddlewareInterface
{
    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        return $next->handle($request, $next);
    }
}
