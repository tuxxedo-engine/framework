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

namespace Tuxxedo\Validator\Rule\Max;

use Tuxxedo\Validator\ViolationContextInterface;

class MaxViolationContext implements ViolationContextInterface
{
    public function __construct(
        public readonly int|float $actual,
        public readonly int|float $max,
    ) {
    }
}
