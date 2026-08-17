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

namespace Tuxxedo\Validator\Rule\Enum;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class EnumRule implements RuleInterface
{
    /**
     * @param class-string<\UnitEnum> $enum
     */
    public function __construct(
        public readonly string $enum,
    ) {
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if ($value === null) {
            return null;
        }

        if ($value instanceof $this->enum) {
            return null;
        }

        return new Violation(
            code: EnumViolationCode::WRONG_INSTANCE,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new EnumViolationContext(
                expected: $this->enum,
                received: \get_debug_type($value),
            ),
        );
    }
}
