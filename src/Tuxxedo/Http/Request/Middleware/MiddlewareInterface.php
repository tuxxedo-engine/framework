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

// @todo Implement TrustProxy for X-Forwarded-* parsing (likely needs Request::withServer())
// @todo Implement Cors for cross-origin requests with preflight handling
// @todo Implement RequestId for correlation IDs via X-Request-Id
// @todo Implement MaintenanceMode for 503 with Retry-After
// @todo Implement MethodOverride for _method/X-HTTP-Method-Override
// @todo Implement MaxBodySize for 413 enforcement on Content-Length
// @todo Implement AccessLog for request/response logging via LoggerInterface
interface MiddlewareInterface
{
    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface;
}
