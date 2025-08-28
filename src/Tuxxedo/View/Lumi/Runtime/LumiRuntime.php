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

class LumiRuntime implements LumiRuntimeInterface
{
    public readonly array $defaultDirectives;
    public private(set) array $directives;

    /**
     * @param array<string, string|int|float|bool|null> $directives
     */
    public function __construct(
        array $directives = [],
    ) {
        $this->defaultDirectives = $directives;
        $this->directives = $directives;
    }

    public function resetDirectives(): void
    {
        $this->directives = $this->defaultDirectives;
    }

    public function functionCall(
        string $function,
        array $arguments = [],
    ): mixed {
        // @todo Properly implement
        return $function(...$arguments);
    }
}
