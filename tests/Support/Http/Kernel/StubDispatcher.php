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

namespace Support\Http\Kernel;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Kernel\DispatcherInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DispatchableRouteInterface;

class StubDispatcher implements DispatcherInterface
{
    public function __construct(
        private readonly ResponseInterface|\Throwable $result,
    ) {
    }

    public function dispatch(
        ContainerInterface $container,
        DispatchableRouteInterface $dispatchableRoute,
        RequestInterface $request,
    ): ResponseInterface {
        if ($this->result instanceof \Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}
