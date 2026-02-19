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

namespace Tuxxedo\View\Lumi\Runtime\Library\Function;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Router\RouterException;
use Tuxxedo\Router\RouterInterface;
use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\ViewRenderInterface;

class RouteFunction implements FunctionInterface
{
    public private(set) string $name = 'route';
    public private(set) array $aliases = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): string {
        /** @var string $name */
        $name = \array_shift($arguments);

        /** @var string[]|array<string, string> $args */
        $args = $arguments;

        return $this->container->resolve(RouterInterface::class)->findByName($name, $args)?->asUrl() ?? throw RouterException::fromInvalidNamedRoute(
            name: $name,
        );
    }
}
