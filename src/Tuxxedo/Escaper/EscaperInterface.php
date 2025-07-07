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

namespace Tuxxedo\Escaper;

interface EscaperInterface
{
    public function html(
        string $input,
    ): string;

    public function attribute(
        string $input,
    ): string;

    public function js(
        string $input,
    ): string;
}
