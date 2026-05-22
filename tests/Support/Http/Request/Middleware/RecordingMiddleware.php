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

namespace Support\Http\Request\Middleware;

use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;

class RecordingMiddleware implements MiddlewareInterface
{
    public int $callCount = 0;
    public ?RequestInterface $lastRequest = null;

    public function __construct(
        private readonly ResponseInterface $response = new Response(),
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $this->callCount++;
        $this->lastRequest = $request;

        return $this->response;
    }
}
