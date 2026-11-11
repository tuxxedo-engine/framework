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

namespace Tuxxedo\Console\Middleware;

use Tuxxedo\Console\ExitCode;
use Tuxxedo\Container\ContainerInterface;

readonly class MiddlewareNode implements CommandMiddlewareInterface
{
    /**
     * @param (\Closure(): CommandMiddlewareInterface)|CommandMiddlewareInterface $current
     */
    public function __construct(
        private ContainerInterface $container,
        private \Closure|CommandMiddlewareInterface $current,
        private CommandMiddlewareInterface $next,
    ) {
    }

    public function handle(
        CommandInvocationInterface $invocation,
        CommandMiddlewareInterface $next,
    ): ExitCode {
        $current = $this->current instanceof \Closure
            ? $this->container->call($this->current)
            : $this->current;

        return $current->handle(
            invocation: $invocation,
            next: $this->next,
        );
    }
}
