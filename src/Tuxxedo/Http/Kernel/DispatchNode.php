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

namespace Tuxxedo\Http\Kernel;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DispatchableRouteInterface;

readonly class DispatchNode implements MiddlewareInterface
{
    public function __construct(
        private DispatchableRouteInterface $dispatchableRoute,
        private DispatcherInterface $dispatcher,
        private ContainerInterface $container,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        return $this->dispatcher->dispatch(
            container: $this->container,
            dispatchableRoute: $this->dispatchableRoute,
            request: $request,
        );
    }
}
