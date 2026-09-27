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

namespace Tuxxedo\Console\Middleware\Attribute;

use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;
use Tuxxedo\Container\ContainerInterface;

#[\Attribute(flags: \Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class CommandMiddleware
{
    /**
     * @param class-string<CommandMiddlewareInterface>|(\Closure(ContainerInterface $container): CommandMiddlewareInterface) $middleware
     */
    public function __construct(
        public string|\Closure $middleware,
    ) {
    }
}
