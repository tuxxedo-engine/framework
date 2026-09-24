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

namespace Tuxxedo\Validator\Rule\Period;

use Tuxxedo\Validator\ViolationContextInterface;

class PeriodViolationContext implements ViolationContextInterface
{
    public function __construct(
        public readonly string $start,
        public readonly string $end,
    ) {
    }
}
