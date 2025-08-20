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

namespace Tuxxedo\Http\Request\Middleware;

use Tuxxedo\Http\Header;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

// @todo Allow customization of the value field
class StrictTransportSecurityMiddleware implements MiddlewareInterface
{
    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $response = $next->handle($request, $next);

        if ($request->server->https) {
            $response->withHeader(
                new Header(
                    name: 'Strict-Transport-Security',
                    value: 'max-age=31536000; includeSubDomains; preload',
                ),
            );
        }

        return $response;
    }
}
