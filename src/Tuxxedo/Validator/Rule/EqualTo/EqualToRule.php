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

namespace Tuxxedo\Validator\Rule\EqualTo;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Intl\Normalize;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class EqualToRule implements RuleInterface
{
    public function __construct(
        public readonly int|float|string|bool|null $expected,
        public readonly Normalize $normalize = Normalize::CANONICAL,
    ) {
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if (\is_string($value) && \is_string($this->expected)) {
            $normalizedValue = $this->normalize->apply($value);
            $normalizedExpected = $this->normalize->apply($this->expected);

            if ($normalizedValue === null || $normalizedExpected === null) {
                return new Violation(
                    code: CommonViolationCode::INVALID_ENCODING,
                    propertyPath: $context->currentPath,
                    invalidValue: $value,
                );
            }

            if ($normalizedValue === $normalizedExpected) {
                return null;
            }
        } elseif ($value === $this->expected) {
            return null;
        }

        return new Violation(
            code: EqualToViolationCode::NOT_EQUAL,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new EqualToViolationContext(
                expected: $this->expected,
            ),
        );
    }
}
