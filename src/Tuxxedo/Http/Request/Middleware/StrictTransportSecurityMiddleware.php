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

class StrictTransportSecurityMiddleware implements MiddlewareInterface
{
    private readonly string $value;

    public function __construct(
        int $maxAge = 31536000,
        bool $includeSubDomains = true,
        bool $preload = true,
    ) {
        $value = \sprintf(
            'max-age=%d',
            $maxAge,
        );

        if ($includeSubDomains) {
            $value .= '; includeSubDomains';
        }

        if ($preload) {
            $value .= '; preload';
        }

        $this->value = $value;
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $response = $next->handle($request, $next);

        if ($request->server->https) {
            $response->withHeader(
                new Header(
                    name: 'Strict-Transport-Security',
                    value: $this->value,
                ),
            );
        }

        return $response;
    }
}
