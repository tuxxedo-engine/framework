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

class AssertContextDto
{
    public function __construct(
        public readonly string $label = 'bad',
    ) {
    }

    /**
     * @return iterable<ViolationInterface>
     */
    #[Assert]
    public function labelFailsWithPathAnchor(
        #[Context] ValidationContextInterface $context,
    ): iterable {
        return [
            new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: $context->currentPath === ''
                    ? 'label'
                    : $context->currentPath . '.label',
                invalidValue: $this->label,
            ),
        ];
    }
}
