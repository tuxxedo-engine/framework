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

namespace Tuxxedo\View\Lumi\Runtime\Library\Function;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Router\RouterException;
use Tuxxedo\Router\RouterInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class RouteFunction implements FunctionInterface
{
    public private(set) string $name = 'route';
    public private(set) array $aliases = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): string {
        /** @var string $name */
        $name = \array_shift($arguments);

        /** @var array<string, string> $args */
        $args = $arguments[0] ?? [];

        return $this->container->resolve(RouterInterface::class)->findByName($name, $args)?->asUrl() ?? throw RouterException::fromInvalidNamedRoute(
            name: $name,
        );
    }
}
