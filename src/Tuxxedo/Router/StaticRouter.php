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

class StaticRouter implements RouterInterface
{
    /**
     * @param RouteInterface[] $routes
     */
    public function __construct(
        private readonly array $routes,
    ) {
    }

    public function findByUri(
        Method $method,
        string $uri,
    ): ?RouteInterface {
        foreach ($this->routes as $route) {
            if ($route->uri !== $uri) {
                continue;
            }

            if ($route->method !== null && $route->method !== $method) {
                throw HttpException::fromMethodNotAllowed();
            }

            return $route;
        }

        return null;
    }

    public function findByRequest(
        RequestInterface $request,
    ): ?RouteInterface {
        return $this->findByUri(
            method: $request->context->method,
            uri: $request->context->uri,
        );
    }
}
