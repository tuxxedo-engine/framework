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

namespace Tuxxedo\View\Lumi\Highlight;

use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

interface HighlighterInterface
{
    /**
     * @throws HighlightException
     */
    public function highlight(
        ThemeInterface|string $theme,
        NodeStreamInterface $stream,
    ): string;
}
