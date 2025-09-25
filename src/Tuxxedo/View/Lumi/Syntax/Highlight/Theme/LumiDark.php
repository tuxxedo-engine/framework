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

namespace Tuxxedo\View\Lumi\Syntax\Highlight\Theme;

use Tuxxedo\View\Lumi\Syntax\Highlight\ColorSlot;

class LumiDark implements ThemeInterface
{
    public function color(
        ColorSlot $slot,
    ): string {
        return match ($slot) {
            ColorSlot::TEXT => '#C9D1D9',
            ColorSlot::COMMENT => '#8B949E',
            ColorSlot::DELIMITER => '#6E7681',
            ColorSlot::KEYWORD => '#FF7B72',
            ColorSlot::OPERATOR => '#79C0FF',
            ColorSlot::STRING => '#7EE787',
            ColorSlot::NUMBER => '#FFA657',
            ColorSlot::BOOL => '#F2CC60',
            ColorSlot::NULL => '#D2A8FF',
            ColorSlot::IDENTIFIER => '#C9D1D9',
            ColorSlot::FUNCTION_NAME => '#D2A8FF',
            ColorSlot::MEMBER_NAME => '#A5D6FF',
            ColorSlot::FILTER_NAME => '#A5D6FF',
            ColorSlot::PIPE => '#6E7681',
            ColorSlot::CONCAT => '#F2CC60',
            ColorSlot::NULL_COALESCE => '#79C0FF',
            ColorSlot::KEY => '#FFA657',
        };
    }
}
