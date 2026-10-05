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

use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\View\Lumi\Library\Attribute\Context;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class ConfigFunctions
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    #[LumiFunction('config')]
    public function config(
        string $path,
    ): mixed {
        return $this->kernel->config->path($path);
    }

    #[LumiFunction('directive')]
    public function directive(
        #[Context]
        RuntimeContextInterface $context,
        string $directive,
    ): string|int|float|bool|null {
        if (!$context->hasDirective($directive)) {
            throw RuntimeException::fromInvalidDirective(
                directive: $directive,
            );
        }

        return $context->directive($directive);
    }

    #[LumiFunction('hasDirective')]
    public function hasDirective(
        #[Context]
        RuntimeContextInterface $context,
        string $directive,
    ): bool {
        return $context->hasDirective($directive);
    }
}
