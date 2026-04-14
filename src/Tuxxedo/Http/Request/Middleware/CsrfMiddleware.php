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

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Security\Csrf\CsrfManagerInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CsrfManagerInterface $csrf,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $method = $request->server->method;

        if (
            $method === Method::POST ||
            $method === Method::PUT ||
            $method === Method::PATCH ||
            $method === Method::DELETE
        ) {
            $token = $request->post->has($this->csrf->fieldName)
                ? $request->post->getString($this->csrf->fieldName)
                : ($request->headers->has('X-Csrf-Token')
                    ? $request->headers->getString('X-Csrf-Token')
                    : '');

            if (!$this->csrf->validate($token)) {
                throw HttpException::fromForbidden();
            }
        }

        return $next->handle($request, $next);
    }
}
