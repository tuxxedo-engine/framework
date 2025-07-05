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

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class MiddlewareNode implements MiddlewareInterface
{
    /**
     * @param \Closure(): MiddlewareInterface $current
     */
    public function __construct(
        private readonly \Closure $current,
        private readonly MiddlewareInterface $next,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        return ($this->current)()->handle(
            request: $request,
            next: $this->next,
        );
    }
};
