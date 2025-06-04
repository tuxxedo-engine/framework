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

class RequestHandlerTail
{
    /**
     * @param (\Closure(Container): ResponseInterface) $resolver
     * @param array<(\Closure(): RequestHandlerInterface)> $middleware
     */
    public function __construct(
        private readonly Container $container,
        private readonly \Closure $resolver,
        private readonly array $middleware,
    ) {
    }

    public function run(
        RequestInterface $request,
    ): ResponseInterface {
        $handler = $this->buildTail();

        return $handler->handle($request, $handler);
    }

    private function buildTail(): RequestHandlerInterface
    {
        $next = new RequestHandler(
            handler: fn(RequestInterface $request): ResponseInterface => ($this->resolver)($this->container),
        );

        foreach ($this->middleware as $middleware) {
            $next = new class ($middleware, $next) implements RequestHandlerInterface {
                /**
                 * @param \Closure(): RequestHandlerInterface $current
                 */
                public function __construct(
                    private readonly \Closure $current,
                    private readonly RequestHandlerInterface $next,
                ) {
                }

                public function handle(
                    RequestInterface $request,
                    RequestHandlerInterface $next,
                ): ResponseInterface {
                    return ($this->current)()->handle(
                        request: $request,
                        next: $this->next,
                    );
                }
            };
        }

        return $next;
    }
}
