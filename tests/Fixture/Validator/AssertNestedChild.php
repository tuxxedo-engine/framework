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
use Tuxxedo\Validator\Attribute\Context;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

class AssertNestedChild
{
    /**
     * @return iterable<ViolationInterface>
     */
    #[Assert]
    public function childAssert(
        #[Context] ValidationContextInterface $context,
    ): iterable {
        return [
            new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: $context->currentPath === ''
                    ? 'child-field'
                    : $context->currentPath . '.child-field',
                invalidValue: null,
            ),
        ];
    }
}
