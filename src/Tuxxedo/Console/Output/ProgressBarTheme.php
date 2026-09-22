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

class ProgressBarTheme implements ProgressBarThemeInterface
{
    public function __construct(
        public readonly int $width,
        public readonly string $filledSegment,
        public readonly string $emptySegment,
        public readonly string $head,
        public readonly string $leadingCap,
        public readonly string $trailingCap,
    ) {
    }

    public static function default(): self
    {
        return new self(
            width: 30,
            filledSegment: '=',
            emptySegment: ' ',
            head: '>',
            leadingCap: '[',
            trailingCap: ']',
        );
    }
}
