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

abstract class AbstractRouter implements RouterInterface
{
    public function findByPath(
        Method|string $method,
        string        $path,
    ): ?DispatchableRouteInterface {
        $isMethodNotAllowed = false;

        if (\is_string($method)) {
            $method = Method::from($method);
        }

        foreach ($this->getRoutes() as $route) {
            $arguments = [];

            if ($route->regexPath !== null) {
                $regex = \preg_match_all($route->regexPath, $path, $arguments, \PREG_SET_ORDER);

                if ($regex === false || $regex === 0) {
                    continue;
                }

                $arguments = \array_filter($arguments[0], \is_string(...), \ARRAY_FILTER_USE_KEY);
            } elseif ($route->path !== $path) {
                continue;
            }

            if ($route->method !== null && $route->method !== $method) {
                $isMethodNotAllowed = true;

                continue;
            }

            return new DispatchableRoute(
                route: $route,
                arguments: $arguments,
            );
        }

        if ($isMethodNotAllowed) {
            throw HttpException::fromMethodNotAllowed();
        }

        return null;
    }

    public function findByRequest(
        RequestInterface $request,
    ): ?DispatchableRouteInterface {
        return $this->findByPath(
            method: $request->method,
            path: $request->path,
        );
    }

    public function findByName(
        string $name,
        array $arguments = [],
        Method|string|null $method = null,
    ): ?DispatchableRouteInterface {
        $isMethodNotAllowed = false;

        if (\is_string($method)) {
            $method = Method::from($method);
        }

        foreach ($this->getRoutes() as $route) {
            if ($route->name !== $name) {
                continue;
            }

            if ($method !== null && $route->method !== $method) {
                $isMethodNotAllowed = true;

                continue;
            }

            return new DispatchableRoute(
                route: $route,
                arguments: $arguments,
            );
        }

        if ($isMethodNotAllowed) {
            throw HttpException::fromMethodNotAllowed();
        }

        return null;
    }
}
