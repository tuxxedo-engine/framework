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

namespace Tuxxedo\Router;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;

// @todo Consider an option to automatically add the final slash as an alias
interface RouterInterface
{
    /**
     * @return iterable<RouteInterface>
     */
    public function getRoutes(): iterable;

    /**
     * @throws HttpException
     */
    public function findByUri(
        Method|string $method,
        string $uri,
    ): ?DispatchableRouteInterface;

    /**
     * @throws HttpException
     */
    public function findByRequest(
        RequestInterface $request,
    ): ?DispatchableRouteInterface;

    /**
     * @param string[]|array<string, string> $arguments
     *
     * @throws HttpException
     */
    public function findByName(
        string $name,
        array $arguments = [],
        Method|string|null $method = null,
    ): ?DispatchableRouteInterface;
}
