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

namespace Tuxxedo\View\Lumi\Syntax\Highlight;

use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Highlight\Theme\ThemeInterface;

class Highlighter implements HighlighterInterface
{
    public function highlight(
        ThemeInterface $theme,
        NodeStreamInterface $stream,
    ): string {
        // @todo
        return '';
    }
}
