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

namespace Tuxxedo\View\Lumi\Runtime;

interface RuntimeContextInterface
{
    public RuntimeFunctionPolicy $functionPolicy {
        get;
    }

    public function hasDirective(
        string $directive,
    ): bool;

    public function directive(
        string $directive,
    ): string|int|float|bool|null;

    public function hasFilter(
        string $filter,
    ): bool;

    public function callFilter(
        mixed $value,
        string $filter,
    ): mixed;

    public function hasFunction(
        string $function,
    ): bool;

    /**
     * @param callable-string $function
     * @param mixed[] $arguments
     */
    public function callFunction(
        string $function,
        array $arguments = [],
    ): mixed;

    public function hasBlock(
        string $name,
    ): bool;
}
