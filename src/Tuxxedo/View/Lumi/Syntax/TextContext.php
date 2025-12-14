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

namespace Tuxxedo\View\Lumi\Syntax;

enum TextContext
{
    case NONE;
    case RAW;

    public static function fromString(
        string $name,
    ): self {
        return match ($name) {
            self::RAW->name => self::RAW,
            default => self::NONE,
        };
    }
}
