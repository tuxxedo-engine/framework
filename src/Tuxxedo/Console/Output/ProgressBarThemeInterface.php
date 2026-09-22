<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Console\Output;

interface ProgressBarThemeInterface
{
    public int $width {
        get;
    }

    public string $filledSegment {
        get;
    }

    public string $emptySegment {
        get;
    }

    public string $head {
        get;
    }

    public string $leadingCap {
        get;
    }

    public string $trailingCap {
        get;
    }
}
