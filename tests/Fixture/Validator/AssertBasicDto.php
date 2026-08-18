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

class AssertBasicDto
{
    public function __construct(
        public readonly string $password = 'secret',
        public readonly string $passwordConfirmation = 'secret',
    ) {
    }

    /**
     * @return iterable<ViolationInterface>
     */
    #[Assert]
    public function passwordsMatch(): iterable
    {
        if ($this->password === $this->passwordConfirmation) {
            return [];
        }

        return [
            new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'passwordConfirmation',
                invalidValue: $this->passwordConfirmation,
            ),
        ];
    }
}
