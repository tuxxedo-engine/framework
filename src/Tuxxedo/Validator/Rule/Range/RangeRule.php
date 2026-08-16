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

namespace Tuxxedo\Validator\Rule\Range;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class RangeRule implements RuleInterface
{
    public function __construct(
        public readonly int|float $min,
        public readonly int|float $max,
        public readonly bool $inclusive = true,
    ) {
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        $numeric = self::coerceNumeric($value);

        if ($numeric === null) {
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

        $belowMin = $this->inclusive
            ? $numeric < $this->min
            : $numeric <= $this->min;

        if ($belowMin) {
            return new Violation(
                code: RangeViolationCode::BELOW_MIN,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new RangeViolationContext(
                    actual: $numeric,
                    min: $this->min,
                    max: $this->max,
                    inclusive: $this->inclusive,
                ),
            );
        }

        $aboveMax = $this->inclusive
            ? $numeric > $this->max
            : $numeric >= $this->max;

        if ($aboveMax) {
            return new Violation(
                code: RangeViolationCode::ABOVE_MAX,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new RangeViolationContext(
                    actual: $numeric,
                    min: $this->min,
                    max: $this->max,
                    inclusive: $this->inclusive,
                ),
            );
        }

        return null;
    }

    private static function coerceNumeric(
        mixed $value,
    ): int|float|null {
        if (\is_int($value) || \is_float($value)) {
            return $value;
        }

        if (\is_string($value) && \is_numeric($value)) {
            return $value + 0;
        }

        return null;
    }
}
