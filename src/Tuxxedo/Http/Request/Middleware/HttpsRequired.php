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
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

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
        if ($request->server->https) {
            return $next->handle($request, $next);
        }

        return Response::redirect(
            uri: \sprintf(
                'https://%s%s%s',
                $request->server->host,
                $request->server->port !== 80 && $request->server->port !== 443
                    ? ':' . \strval($request->server->port)
                    : '',
                $request->server->fullUri,
            ),
            responseCode: $this->responseCode,
        );
    }
}
