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

namespace Tuxxedo\Http\Request\Handler;

use Tuxxedo\Container\Container;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class RequestHandlerPipeline
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
            handler: fn (RequestInterface $request): ResponseInterface => ($this->resolver)($this->container),
        );

        foreach ($this->middleware as $middleware) {
            $next = new RequestHandlerNode(
                current: $middleware,
                next: $next,
            );
        }

        return $next;
    }
}
