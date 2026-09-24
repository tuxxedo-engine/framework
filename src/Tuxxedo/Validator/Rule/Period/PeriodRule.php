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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Temporal\PeriodInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class PeriodRule implements RuleInterface
{
    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof PeriodInterface) {
            return new Violation(
                code: CommonViolationCode::WRONG_TYPE,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new WrongTypeViolationContext(
                    expected: PeriodInterface::class,
                    received: \get_debug_type($value),
                ),
            );
        }

        if ($value->end->isBefore(other: $value->start)) {
            return new Violation(
                code: PeriodViolationCode::INVERTED,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new PeriodViolationContext(
                    start: $value->start->toIso8601(),
                    end: $value->end->toIso8601(),
                ),
            );
        }

        return null;
    }
}
