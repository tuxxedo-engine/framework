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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\Attribute\Middleware;

class RouteMiddlewareResolver implements RouteMiddlewareResolverInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function attributes(
        \ReflectionClass|\ReflectionMethod $reflector,
    ): array {
        $middleware = [];
        $container = $this->container;

        foreach ($reflector->getAttributes(Middleware::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $entry = $attribute->newInstance()->middleware;

            if (\is_string($entry)) {
                $middleware[] = static fn (): MiddlewareInterface => /** @var MiddlewareInterface */ $container->resolve($entry);
            } else {
                $middleware[] = static fn (): MiddlewareInterface => $entry($container);
            }
        }

        return $middleware;
    }

    public function normalize(
        array $middleware,
    ): array {
        $normalized = [];
        $container = $this->container;

        foreach ($middleware as $entry) {
            if ($entry instanceof \Closure) {
                $normalized[] = $entry;

                continue;
            }

            $normalized[] = static fn (): MiddlewareInterface => /** @var MiddlewareInterface */ $container->resolve($entry);
        }

        return $normalized;
    }
}
