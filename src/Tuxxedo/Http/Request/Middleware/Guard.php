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

namespace Tuxxedo\Http\Request\Middleware;

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

readonly class Guard implements MiddlewareInterface
{
    /**
     * @param \Closure(RequestInterface $request, MiddlewareInterface $next): ResponseInterface $guard
     */
    public function __construct(
        private \Closure $guard,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        return ($this->guard)($request, $next);
    }
}
