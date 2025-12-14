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

namespace Tuxxedo\View\Lumi\Highlight\Theme;

use Tuxxedo\View\Lumi\Highlight\ColorSlot;

interface ThemeInterface
{
    public string $identifier {
        get;
    }

    public function color(
        ColorSlot $slot,
    ): string;
}
