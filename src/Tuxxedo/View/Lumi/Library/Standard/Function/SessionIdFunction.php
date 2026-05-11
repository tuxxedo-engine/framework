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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Session\SessionInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class SessionIdFunction implements FunctionInterface
{
    public private(set) string $name = 'sessionId';
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
        return $this->container->resolve(SessionInterface::class)->getIdentifier();
    }
}
