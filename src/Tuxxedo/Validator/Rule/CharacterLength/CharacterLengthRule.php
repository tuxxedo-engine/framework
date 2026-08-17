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

namespace Tuxxedo\Validator\Rule\CharacterLength;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class CharacterLengthRule implements RuleInterface
{
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly string $encoding = 'UTF-8',
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

        if (!\is_string($value)) {
            return new Violation(
                code: CommonViolationCode::WRONG_TYPE,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new WrongTypeViolationContext(
                    expected: 'string',
                    received: \get_debug_type($value),
                ),
            );
        }

        $length = \mb_strlen($value, $this->encoding);

        if ($this->min !== null && $length < $this->min) {
            return new Violation(
                code: CharacterLengthViolationCode::TOO_SHORT,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new CharacterLengthViolationContext(
                    actual: $length,
                    min: $this->min,
                ),
            );
        }

        if ($this->max !== null && $length > $this->max) {
            return new Violation(
                code: CharacterLengthViolationCode::TOO_LONG,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new CharacterLengthViolationContext(
                    actual: $length,
                    max: $this->max,
                ),
            );
        }

        return null;
    }
}
