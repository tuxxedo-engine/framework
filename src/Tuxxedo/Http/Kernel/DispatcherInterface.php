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
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DispatchableRouteInterface;

interface DispatcherInterface
{
    /**
     * @throws HttpException
     */
    public function dispatch(
        ContainerInterface $container,
        DispatchableRouteInterface $dispatchableRoute,
        RequestInterface $request,
    ): ResponseInterface;
}
