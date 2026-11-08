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

namespace Fixture\Serializer;

use Fixture\Validator\FixtureViolationCode;
use Tuxxedo\Validator\Attribute\Assert;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

class Range
{
    public function __construct(
        public readonly int $from,
        public readonly int $to,
    ) {
    }

    /**
     * @return iterable<ViolationInterface>
     */
    #[Assert]
    public function ordered(): iterable
    {
        if ($this->from <= $this->to) {
            return [];
        }

        return [
            new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: '',
                invalidValue: $this->from,
            ),
        ];
    }
}
