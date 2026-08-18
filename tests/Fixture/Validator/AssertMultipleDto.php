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

class AssertMultipleDto
{
    /**
     * @return iterable<ViolationInterface>
     */
    #[Assert]
    public function firstAssert(): iterable
    {
        return [
            new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'first',
                invalidValue: null,
            ),
        ];
    }

    /**
     * @return iterable<ViolationInterface>
     */
    #[Assert]
    public function secondAssert(): iterable
    {
        return [
            new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'second-a',
                invalidValue: null,
            ),
            new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'second-b',
                invalidValue: null,
            ),
        ];
    }
}
