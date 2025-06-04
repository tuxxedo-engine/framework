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

use Tuxxedo\Container\Container;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Middleware\MiddlewareInterface;

class RequestHandlerTail
{
    /**
     * @param array<(\Closure(): MiddlewareInterface)> $middleware
     */
    public function __construct(
        private Container $container,
        private ResponseInterface $response,
        private readonly array $middleware,
    ) {
    }

    public function run(
        RequestInterface $request,
    ): ResponseInterface {
        $handler = $this->buildHandler(
            middleware: \array_reverse($this->middleware),
        );

        return $handler->handle($request, $handler);
    }

    /**
     * @param array<(\Closure(): MiddlewareInterface)> $middleware
     */
    private function buildHandler(
        array $middleware,
    ): RequestHandlerInterface {
        $next = new RequestHandler(
            handler: fn(RequestInterface $request): ResponseInterface => $this->response,
        );

        foreach ($middleware as $resolver) {
            $next = new RequestHandler(
                handler: function (RequestInterface $request) use ($resolver, $next): ResponseInterface {
                    ($resolver())->handle(
                        container: $this->container,
                        request: $request,
                    );

                    return $next->handle(
                        request: $request,
                        next: $next,
                    );
                }
            );
        }

        return $next;
    }
}
