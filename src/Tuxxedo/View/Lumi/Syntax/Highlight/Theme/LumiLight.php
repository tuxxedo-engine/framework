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

class LumiLight implements ThemeInterface
{
    public function color(
        ColorSlot $slot,
    ): string {
        return match ($slot) {
            ColorSlot::TEXT => '#24292F',
            ColorSlot::COMMENT => '#6E7781',
            ColorSlot::DELIMITER => '#57606A',
            ColorSlot::KEYWORD => '#CF222E',
            ColorSlot::OPERATOR => '#0550AE',
            ColorSlot::STRING => '#0A7F24',
            ColorSlot::NUMBER => '#953800',
            ColorSlot::BOOL => '#A15C0F',
            ColorSlot::NULL => '#6F42C1',
            ColorSlot::IDENTIFIER => '#0A3069',
            ColorSlot::FUNCTION_NAME => '#8250DF',
            ColorSlot::MEMBER_NAME => '#1F6FEB',
            ColorSlot::FILTER_NAME => '#1A7F37',
            ColorSlot::PIPE => '#57606A',
            ColorSlot::CONCAT => '#BF8700',
            ColorSlot::NULL_COALESCE => '#0550AE',
        };
    }
}
