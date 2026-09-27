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

namespace Console\Middleware;

use Tuxxedo\Application\Environment;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Kernel\KernelInterface;
use Tuxxedo\Console\Middleware\CommandInvocationInterface;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class DevelopmentOnly implements CommandMiddlewareInterface
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    public function handle(
        CommandInvocationInterface $invocation,
        CommandMiddlewareInterface $next,
    ): ExitCode {
        if ($this->kernel->appEnvironment === Environment::DEVELOPMENT) {
            return $next->handle($invocation, $next);
        }

        throw DevelopmentOnlyException::fromCommandRejection(
            commandPath: $invocation->descriptor->path,
            currentEnvironment: $this->kernel->appEnvironment,
        );
    }
}
