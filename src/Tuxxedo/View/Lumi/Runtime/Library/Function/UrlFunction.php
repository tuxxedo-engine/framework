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
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeFrameInterface;

class UrlFunction implements FunctionInterface
{
    public private(set) string $name = 'url';
    public private(set) array $aliases = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @param \Closure(): RuntimeFrameInterface $frame
     */
    public function call(
        array $arguments,
        \Closure $frame,
    ): string {
        /** @var string $path */
        $path = $arguments[0];

        return $this->container->resolve(UrlInterface::class)->get($path);
    }
}
