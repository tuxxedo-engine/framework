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

namespace Tuxxedo\Validator\Rule\Integer;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class IntegerRule implements RuleInterface
{
    public function __construct(
        public readonly bool $strict = true,
    ) {
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if (\is_int($value)) {
            return null;
        }

        if (!$this->strict && \is_string($value) && \filter_var($value, \FILTER_VALIDATE_INT) !== false) {
            return null;
        }

        return new Violation(
            code: CommonViolationCode::WRONG_TYPE,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new WrongTypeViolationContext(
                expected: 'integer',
                received: \get_debug_type($value),
            ),
        );
    }
}
