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

use Tuxxedo\View\Lumi\Highlight\HighlightException;

interface ThemeFactoryInterface
{
    /**
     * @var ThemeInterface[] $themes
     */
    public array $themes {
        get;
    }

    /**
     * @throws HighlightException
     */
    public function find(
        string $identifier,
    ): ThemeInterface;
}
