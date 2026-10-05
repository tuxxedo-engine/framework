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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class MathFunctions
{
    #[LumiFunction('isEven')]
    public function isEven(
        int $input,
    ): bool {
        return $input % 2 === 0;
    }

    #[LumiFunction('isOdd')]
    public function isOdd(
        int $input,
    ): bool {
        return $input % 2 !== 0;
    }

    #[LumiFunction('round')]
    public function round(
        int|float $number,
    ): int|float {
        return \round($number);
    }
}
