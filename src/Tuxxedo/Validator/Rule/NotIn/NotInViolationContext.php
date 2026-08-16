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

namespace Tuxxedo\Validator\Rule\NotIn;

use Tuxxedo\Validator\ViolationContextInterface;

class NotInViolationContext implements ViolationContextInterface
{
    /**
     * @param list<string|int> $disallowed
     */
    public function __construct(
        public readonly array $disallowed,
    ) {
    }
}
