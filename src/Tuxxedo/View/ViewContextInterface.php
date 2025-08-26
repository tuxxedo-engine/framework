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

namespace Tuxxedo\View;

interface ViewContextInterface
{
    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $directives {
        get;
    }

    /**
     * @param callable-string $functionName
     */
    public function functionCall(
        string $functionName,
    ): mixed;

    public function resetDirectives(): void;

    public function directive(
        string $directive,
        string|int|float|bool|null $value,
    ): void;

    /**
     * @param array<string, mixed> $scope
     */
    public function include(
        string $viewName,
        array $scope = [],
    ): string;

    public function escapeHtml(
        string $input,
    ): string;

    public function escapeAttribute(
        string $input,
    ): string;

    public function escapeJs(
        string $input,
    ): string;

    public function escapeUrl(
        string $input,
    ): string;
}
