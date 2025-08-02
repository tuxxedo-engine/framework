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

abstract class AbstractRouter implements RouterInterface
{
    public function findByUri(
        Method $method,
        string $uri,
    ): ?DispatchableRouteInterface {
        $isMethodNotAllowed = false;

        foreach ($this->getRoutes() as $route) {
            $arguments = [];

            if ($route->regexUri !== null) {
                $regex = \preg_match_all($route->regexUri, $uri, $arguments, \PREG_SET_ORDER);

                if ($regex === false || $regex === 0) {
                    continue;
                }

                $arguments = $arguments[0];
            } elseif ($route->uri !== $uri) {
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
        return $this->findByUri(
            method: $request->server->method,
            uri: $request->server->uri,
        );
    }
}
