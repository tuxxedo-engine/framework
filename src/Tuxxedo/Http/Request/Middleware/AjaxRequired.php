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

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class AjaxRequired implements MiddlewareInterface
{
    /**
     * @throws HttpException
     */
    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if (
            !$request->headers->has('X-Requested-With') ||
            $request->headers->getString('X-Requested-With') !== 'XMLHttpRequest'
        ) {
            throw HttpException::fromForbidden();
        }

        return $next->handle($request, $next);
    }
}
