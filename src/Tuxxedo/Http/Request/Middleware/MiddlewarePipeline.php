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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

// @todo Consider baking this into the Kernel
class MiddlewarePipeline
{
    /**
     * @param (\Closure(ContainerInterface): ResponseInterface) $resolver
     * @param array<(\Closure(): MiddlewareInterface)> $middleware
     */
    public function __construct(
        private readonly ContainerInterface $container,
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
             * @param (\Closure(ContainerInterface): ResponseInterface) $resolver
             */
            public function __construct(
                private \Closure $resolver,
                private ContainerInterface $container,
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
