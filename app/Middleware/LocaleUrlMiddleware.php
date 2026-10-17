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

namespace App\Middleware;

use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DispatchableRoute;

class LocaleUrlMiddleware implements MiddlewareInterface
{
    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if (
            !$request->hasRoute() ||
            (!$request->isGet() && !$request->isHead())
        ) {
            return $next->handle($request, $next);
        }

        $language = $request->route->arguments['language'] ?? '';
        $canonical = \mb_strtolower($language);

        if ($language === $canonical) {
            return $next->handle($request, $next);
        }

        $url = (new DispatchableRoute(
            route: $request->route->route,
            arguments: [
                ...$request->route->arguments,
                'language' => $canonical,
            ],
        ))->asUrl();

        if ($url === null) {
            return $next->handle($request, $next);
        }

        return Response::redirect(
            uri: $request->queryString !== ''
                ? $url . '?' . $request->queryString
                : $url,
            responseCode: ResponseCode::MOVED_PERMANENTLY,
        );
    }
}
