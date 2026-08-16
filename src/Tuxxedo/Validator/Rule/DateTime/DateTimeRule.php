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

namespace Tuxxedo\Validator\Rule\DateTime;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class DateTimeRule implements RuleInterface
{
    public function __construct(
        public readonly ?string $format = null,
    ) {
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
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

        if ($this->format !== null) {
            $parsed = \DateTimeImmutable::createFromFormat($this->format, $value);

            if ($parsed !== false && $parsed->format($this->format) === $value) {
                return null;
            }
        } else {
            try {
                new \DateTimeImmutable($value);

                return null;
            } catch (\Exception) {
            }
        }

        return new Violation(
            code: DateTimeViolationCode::INVALID_FORMAT,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new DateTimeViolationContext(
                format: $this->format,
            ),
        );
    }
}
