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

namespace Tuxxedo\View\Lumi\Runtime;

interface LumiRuntimeInterface
{
    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $directives {
        get;
    }

    public function resetDirectives(): void;

    /**
     * @param callable-string $function
     * @param mixed[] $arguments
     */
    public function functionCall(
        string $function,
        array $arguments = [],
    ): mixed;
}
