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

namespace Tuxxedo\Validator\Rule\Numeric;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class NumericRule implements RuleInterface
{
    public function __construct(
        public readonly bool $strict = false,
    ) {
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        $isValid = $this->strict
            ? \is_int($value) || \is_float($value)
            : \is_numeric($value);

        if ($isValid) {
            return null;
        }

        return new Violation(
            code: CommonViolationCode::WRONG_TYPE,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new WrongTypeViolationContext(
                expected: 'numeric',
                received: \get_debug_type($value),
            ),
        );
    }
}
