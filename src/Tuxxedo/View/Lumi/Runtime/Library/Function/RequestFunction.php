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
use Tuxxedo\Http\Request\Context\ServerContextInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\ViewRenderInterface;

class RequestFunction implements FunctionInterface
{
    public private(set) string $name = 'request';
    public private(set) array $aliases = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): ServerContextInterface {
        return $this->container->resolve(RequestInterface::class)->server;
    }
}
