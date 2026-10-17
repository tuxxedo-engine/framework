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
    /**
     * @var array<string, array<string, RouteInterface>>|null
     */
    private ?array $staticByMethod = null;

    /**
     * @var array<string, RouteInterface>|null
     */
    private ?array $staticAny = null;

    /**
     * @var list<RouteInterface>|null
     */
    private ?array $dynamic = null;

    /**
     * @var array<string, list<RouteInterface>>|null
     */
    private ?array $namedRoutes = null;

    public function findByPath(
        Method|string $method,
        string $path,
    ): ?DispatchableRouteInterface {
        if (\is_string($method)) {
            $method = Method::from($method);
        }

        $this->indexIfNeeded();

        $methodName = $method->name;

        if (isset($this->staticByMethod[$methodName][$path])) {
            return new DispatchableRoute(
                route: $this->staticByMethod[$methodName][$path],
                arguments: [],
            );
        }

        if (isset($this->staticAny[$path])) {
            return new DispatchableRoute(
                route: $this->staticAny[$path],
                arguments: [],
            );
        }

        $isMethodNotAllowed = $this->staticPathExistsForOtherMethod($path, $methodName);

        /** @var list<RouteInterface> $dynamicRoutes */
        $dynamicRoutes = $this->dynamic ?? [];

        foreach ($dynamicRoutes as $route) {
            /** @var string $regex */
            $regex = $route->regexPath;
            $arguments = [];
            $matches = \preg_match_all($regex, $path, $arguments, \PREG_SET_ORDER);

            if ($matches === false || $matches === 0) {
                continue;
            }

            $arguments = \array_filter($arguments[0], \is_string(...), \ARRAY_FILTER_USE_KEY);

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
        ?DispatchableRouteInterface $current = null,
    ): ?DispatchableRouteInterface {
        if (\is_string($method)) {
            $method = Method::from($method);
        }

        $this->indexIfNeeded();

        /** @var array<string, list<RouteInterface>> $named */
        $named = $this->namedRoutes ?? [];

        if (!isset($named[$name])) {
            return null;
        }

        $isMethodNotAllowed = false;

        foreach ($named[$name] as $route) {
            if ($method !== null && $route->method !== $method) {
                $isMethodNotAllowed = true;

                continue;
            }

            return new DispatchableRoute(
                route: $route,
                arguments: self::withStickyArguments(
                    route: $route,
                    arguments: $arguments,
                    current: $current,
                ),
            );
        }

        if ($isMethodNotAllowed) {
            throw HttpException::fromMethodNotAllowed();
        }

        return null; // @codeCoverageIgnore
    }

    /**
     * @param array<string, string|null> $arguments
     * @return array<string, string>
     */
    private static function withStickyArguments(
        RouteInterface $route,
        array $arguments,
        ?DispatchableRouteInterface $current,
    ): array {
        if (
            $current !== null &&
            $route->stickyPrefix !== null &&
            $route->stickyPrefix === $current->route->stickyPrefix
        ) {
            foreach ($route->arguments as $argument) {
                $name = $argument->node->name;

                if (
                    $argument->node->prefixed &&
                    !\array_key_exists($name, $arguments) &&
                    ($current->arguments[$name] ?? '') !== ''
                ) {
                    $arguments[$name] = $current->arguments[$name];
                }
            }
        }

        return \array_filter(
            $arguments,
            static fn (?string $value): bool => $value !== null,
        );
    }

    private function indexIfNeeded(): void
    {
        if ($this->staticByMethod !== null) {
            return;
        }

        /** @var array<string, array<string, RouteInterface>> $staticByMethod */
        $staticByMethod = [];

        /** @var array<string, RouteInterface> $staticAny */
        $staticAny = [];

        /** @var list<RouteInterface> $dynamic */
        $dynamic = [];

        /** @var array<string, list<RouteInterface>> $named */
        $named = [];

        foreach ($this->getRoutes() as $route) {
            if ($route->name !== null) {
                $named[$route->name][] = $route;
            }

            if ($route->regexPath !== null) {
                $dynamic[] = $route;

                continue;
            }

            if ($route->method !== null) {
                $methodName = $route->method->name;

                if (!isset($staticByMethod[$methodName][$route->path])) {
                    $staticByMethod[$methodName][$route->path] = $route;
                }

                continue;
            }

            if (!isset($staticAny[$route->path])) {
                $staticAny[$route->path] = $route;
            }
        }

        $this->staticByMethod = $staticByMethod;
        $this->staticAny = $staticAny;
        $this->dynamic = $dynamic;
        $this->namedRoutes = $named;
    }

    private function staticPathExistsForOtherMethod(
        string $path,
        string $methodName,
    ): bool {
        /** @var array<string, array<string, RouteInterface>> $staticByMethod */
        $staticByMethod = $this->staticByMethod ?? [];

        foreach ($staticByMethod as $registeredMethod => $paths) {
            if ($registeredMethod === $methodName) {
                continue;
            }

            if (isset($paths[$path])) {
                return true;
            }
        }

        return false;
    }
}
