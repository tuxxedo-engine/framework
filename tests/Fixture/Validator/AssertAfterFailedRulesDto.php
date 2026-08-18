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

namespace Fixture\Validator;

use Tuxxedo\Validator\Attribute\Assert;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

class AssertAfterFailedRulesDto
{
    public function __construct(
        #[AlwaysFailRule]
        public readonly string $field = 'anything',
    ) {
    }

    /**
     * @return iterable<ViolationInterface>
     */
    #[Assert]
    public function assertAlsoFails(): iterable
    {
        return [
            new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'assert-emitted',
                invalidValue: null,
            ),
        ];
    }
}
