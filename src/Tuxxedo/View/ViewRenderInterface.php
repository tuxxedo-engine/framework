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
    public ViewContextInterface $context {
        get;
    }

    public function getViewFileName(
        string $viewName,
    ): string;

    public function viewExists(
        string $viewName,
    ): bool;

    /**
     * @throws ViewException
     */
    public function render(
        ViewInterface $view,
    ): string;
}
