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
     * @var RouteInterface[]
     */
    private readonly array $routes;

    /**
     * @param RouteInterface[] $routes
     */
    public function __construct(
        array $routes,
    ) {
        \uasort(
            $routes,
            static fn (RouteInterface $a, RouteInterface $b): int => $a->priority->value <=> $b->priority->value,
        );

        $this->routes = $routes;
    }

    public function findByUri(
        Method $method,
        string $uri,
    ): ?RouteInterface {
        $isMethodNotAllowed = false;

        foreach ($this->routes as $route) {
            if ($route->uri !== $uri) {
                continue;
            }

            if ($route->method !== null && $route->method !== $method) {
                $isMethodNotAllowed = true;

                continue;
            }

            return $route;
        }

        if ($isMethodNotAllowed) {
            throw HttpException::fromMethodNotAllowed();
        }

        return null;
    }

    public function findByRequest(
        RequestInterface $request,
    ): ?RouteInterface {
        return $this->findByUri(
            method: $request->server->method,
            uri: $request->server->uri,
        );
    }
}
