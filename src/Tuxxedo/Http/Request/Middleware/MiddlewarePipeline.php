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

use Tuxxedo\Container\Container;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class MiddlewarePipeline
{
    /**
     * @param (\Closure(Container): ResponseInterface) $resolver
     * @param array<(\Closure(): MiddlewareInterface)> $middleware
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
        $handler = $this->buildPipeline();

        return $handler->handle($request, $handler);
    }

    private function buildPipeline(): MiddlewareInterface
    {
        $next = new class ($this->resolver, $this->container) implements MiddlewareInterface {
            /**
             * @param (\Closure(Container): ResponseInterface) $resolver
             */
            public function __construct(
                private \Closure $resolver,
                private Container $container,
            ) {
            }

            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                return ($this->resolver)($this->container);
            }
        };

        foreach ($this->middleware as $middleware) {
            $next = new MiddlewareNode(
                current: $middleware,
                next: $next,
            );
        }

        return $next;
    }
}
