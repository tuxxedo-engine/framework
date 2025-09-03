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

namespace Tuxxedo\View\Lumi\Compiler\Optimizer;

enum DceEvaluateResult
{
    case ALWAYS_FALSE;
    case ALWAYS_TRUE;
    case CANNOT_DETERMINE;

    public static function fromBool(
        bool $result,
    ): self {
        return $result
            ? self::ALWAYS_TRUE
            : self::ALWAYS_FALSE;
    }
}
