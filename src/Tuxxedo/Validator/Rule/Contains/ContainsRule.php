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

namespace Tuxxedo\Validator\Rule\Contains;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Intl\Normalize;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class ContainsRule implements RuleInterface
{
    public function __construct(
        public readonly string $needle,
        public readonly Normalize $normalize = Normalize::CANONICAL,
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

        $normalizedValue = $this->normalize->apply($value);
        $normalizedNeedle = $this->normalize->apply($this->needle);

        if ($normalizedValue === null || $normalizedNeedle === null) {
            return new Violation(
                code: CommonViolationCode::INVALID_ENCODING,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        if (!\str_contains($normalizedValue, $normalizedNeedle)) {
            return new Violation(
                code: ContainsViolationCode::MISSING_SUBSTRING,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new ContainsViolationContext(
                    needle: $this->needle,
                ),
            );
        }

        return null;
    }
}
