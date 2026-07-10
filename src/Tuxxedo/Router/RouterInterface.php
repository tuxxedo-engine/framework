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

namespace Tuxxedo\Router;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;

// @todo Emit Allow header on 405 responses (RFC 9110 §15.5.6); include QUERY when a route registers it
interface RouterInterface
{
    /**
     * @return iterable<RouteInterface>
     */
    public function getRoutes(): iterable;

    /**
     * @throws HttpException
     */
    public function findByPath(
        Method|string $method,
        string $path,
    ): ?DispatchableRouteInterface;

    /**
     * @throws HttpException
     */
    public function findByRequest(
        RequestInterface $request,
    ): ?DispatchableRouteInterface;

    /**
     * @param array<string, string> $arguments
     *
     * @throws HttpException
     */
    public function findByName(
        string $name,
        array $arguments = [],
        Method|string|null $method = null,
    ): ?DispatchableRouteInterface;
}
