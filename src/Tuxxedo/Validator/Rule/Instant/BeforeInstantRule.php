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

namespace Tuxxedo\Validator\Rule\Instant;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class BeforeInstantRule implements RuleInterface
{
    private readonly InstantInterface $anchor;

    public function __construct(
        InstantInterface|string $anchor,
    ) {
        $this->anchor = $anchor instanceof InstantInterface
            ? $anchor
            : Instant::parse(input: $anchor);
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if ($value === null) {
            return null;
        }

        $subject = InstantCoercion::coerce(value: $value);

        if ($subject === null) {
            return new Violation(
                code: CommonViolationCode::WRONG_TYPE,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new WrongTypeViolationContext(
                    expected: InstantInterface::class,
                    received: \get_debug_type($value),
                ),
            );
        }

        if ($subject->isBefore(other: $this->anchor)) {
            return null;
        }

        return new Violation(
            code: InstantOrderViolationCode::NOT_BEFORE,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new InstantOrderViolationContext(
                anchor: $this->anchor->toIso8601(),
            ),
        );
    }
}
