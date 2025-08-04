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

// @todo Support include()
interface ViewContextInterface
{
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
