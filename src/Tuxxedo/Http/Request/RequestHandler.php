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

namespace Tuxxedo\Http\Request;

use Tuxxedo\Http\Response\ResponseInterface;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * @param (\Closure(RequestInterface): ResponseInterface) $handler
     */
    public function __construct(
        private readonly \Closure $handler,
    ) {
    }

    public function handle(
        RequestInterface $request,
        RequestHandlerInterface $next,
    ): ResponseInterface {
        return ($this->handler)($request);
    }
}
