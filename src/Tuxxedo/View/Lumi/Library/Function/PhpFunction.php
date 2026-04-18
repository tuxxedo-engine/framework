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

class PhpFunction implements FunctionInterface
{
    /**
     * @param string[] $aliases
     * @param callable-string|null $mappedName
     */
    public function __construct(
        public readonly string $name,
        public array $aliases = [],
        private readonly ?string $mappedName = null,
    ) {
    }

    public function call(
        array $arguments,
        \Closure $context,
    ): mixed {
        /** @var callable-string $function */
        $function = $this->mappedName ?? $this->name;

        return \call_user_func_array($function, $arguments);
    }
}
