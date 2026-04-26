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

readonly class DispatchableRoute implements DispatchableRouteInterface
{
    /**
     * @param array<string, string> $arguments
     */
    public function __construct(
        public RouteInterface $route,
        public array $arguments = [],
    ) {
    }

    public function asUrl(): ?string
    {
        if ($this->route->arguments === []) {
            return $this->route->uri;
        }

        foreach ($this->route->arguments as $routeArgument) {
            if (
                !$routeArgument->node->optional &&
                !\array_key_exists($routeArgument->node->name, $this->arguments) &&
                (
                    $routeArgument->mappedName === null ||
                    !\array_key_exists($routeArgument->mappedName, $this->arguments)
                )
            ) {
                return null;
            }
        }

        return \preg_replace_callback(
            '/\\/\\{(\\??)([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+)|<([^>]+)>)?}/',
            function (array $matches): string {
                $name = $matches[2];
                $value = $this->arguments[$name] ?? null;

                if ($value === null) {
                    foreach ($this->route->arguments as $routeArgument) {
                        if (
                            $routeArgument->node->name === $name &&
                            $routeArgument->mappedName !== null &&
                            \array_key_exists($routeArgument->mappedName, $this->arguments)
                        ) {
                            $value = $this->arguments[$routeArgument->mappedName];
                            break;
                        }
                    }
                }

                if ($value === null) {
                    return '';
                }

                return '/' . $value;
            },
            $this->route->uri,
        );
    }
}
