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

class AssertServiceDto
{
    public function __construct(
        public readonly int $number = 3,
    ) {
    }

    /**
     * @return iterable<ViolationInterface>
     */
    #[Assert]
    public function numberIsEven(
        ParityCheckerInterface $checker,
    ): iterable {
        if ($checker->isEven($this->number)) {
            return [];
        }

        return [
            new Violation(
                code: FixtureViolationCode::ODD_NUMBER,
                propertyPath: 'number',
                invalidValue: $this->number,
                context: new ContainerAwareRuleContext(
                    received: $this->number,
                ),
            ),
        ];
    }
}
