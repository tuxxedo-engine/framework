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

use Tuxxedo\Console\Output\Style\Style;

class SpinnerTheme
{
    public function __construct(
        public readonly FrameSequence $frames,
        public readonly ?Style $frameStyle = null,
        public readonly ?Style $messageStyle = null,
    ) {
    }

    public static function default(): self
    {
        return new self(
            frames: FrameSequence::brailleDots(),
        );
    }
}
