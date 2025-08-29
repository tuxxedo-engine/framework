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

use Tuxxedo\View\ViewException;

interface LumiRuntimeInterface
{
    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $defaultDirectives {
        get;
    }

    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $directives {
        get;
    }

    /**
     * @var string[]
     */
    public array $functions {
        get;
    }

    /**
     * @var array<string, \Closure(string $function, array<mixed> $arguments, LumiDirectivesInterface $directives): mixed>
     */
    public array $customFunctions {
        get;
    }

    public LumiRuntimeFunctionMode $functionMode {
        get;
    }

    public function resetDirectives(): void;

    public function directive(
        string $directive,
        string|int|float|bool|null $value,
    ): void;

    /**
     * @param callable-string $function
     * @param mixed[] $arguments
     *
     * @throws ViewException
     */
    public function functionCall(
        string $function,
        array $arguments = [],
    ): mixed;
}
