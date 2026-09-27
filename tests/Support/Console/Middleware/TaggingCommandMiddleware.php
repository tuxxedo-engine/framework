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

namespace Support\Console\Middleware;

use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Middleware\CommandInvocationInterface;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;

class TaggingCommandMiddleware implements CommandMiddlewareInterface
{
    public function __construct(
        private readonly string $tag,
        private readonly MiddlewareCallSequence $sequence,
    ) {
    }

    public function handle(
        CommandInvocationInterface $invocation,
        CommandMiddlewareInterface $next,
    ): ExitCode {
        $this->sequence->tags[] = $this->tag;

        return $next->handle(
            invocation: $invocation,
            next: $next,
        );
    }
}
