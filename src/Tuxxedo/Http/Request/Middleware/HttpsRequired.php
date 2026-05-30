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
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class HttpsRequired implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseCode $responseCode = ResponseCode::MOVED_PERMANENTLY,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if ($request->https) {
            return $next->handle($request, $next);
        }

        return Response::redirect(
            uri: \sprintf(
                'https://%s%s%s',
                $request->host,
                $request->port !== 80 && $request->port !== 443
                    ? ':' . \strval($request->port)
                    : '',
                $request->fullUri,
            ),
            responseCode: $this->responseCode,
        );
    }
}
