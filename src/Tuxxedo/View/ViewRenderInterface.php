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

interface ViewRenderInterface
{
    public function getViewFileName(
        string $view,
    ): string;

    public function viewExists(
        string $view,
    ): bool;

    /**
     * @param array<string, string|int|float|bool|null>|null $directives
     *
     * @throws ViewException
     */
    public function render(
        ViewInterface $view,
        ?array $directives = null,
    ): string;
}
