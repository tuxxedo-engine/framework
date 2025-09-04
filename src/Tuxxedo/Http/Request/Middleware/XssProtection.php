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

// @todo Allow customization of the headers
class XssProtection implements MiddlewareInterface
{
    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        return $next->handle($request, $next)
            ->withHeaders(
                [
                    new Header('Content-Security-Policy', 'default-src \'self\''),
                    new Header('X-XSS-Protection', '1; mode=block'),
                    new Header('X-Content-Type-Options', 'nosniff'),
                ],
            );
    }
}
