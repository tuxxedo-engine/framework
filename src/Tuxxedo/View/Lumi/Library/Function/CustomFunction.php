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

namespace Tuxxedo\View\Lumi\Library\Function;

class CustomFunction implements FunctionInterface
{
    /**
     * @param \Closure(mixed[], \Closure): mixed $implementation
     * @param string[] $aliases
     */
    public function __construct(
        public readonly string $name,
        private readonly \Closure $implementation,
        public array $aliases = [],
    ) {
    }

    public function call(
        array $arguments,
        \Closure $context,
    ): mixed {
        return ($this->implementation)($arguments, $context);
    }
}
