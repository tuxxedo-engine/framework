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

namespace Tuxxedo\Validator\Rule\NotSuspicious;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class NotSuspiciousRule implements RuleInterface
{
    public function __construct(
        public readonly SpoofLevel $level = SpoofLevel::HIGHLY_RESTRICTIVE,
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

        $checker = new \Spoofchecker();

        $checker->setRestrictionLevel(
            match ($this->level) {
                SpoofLevel::ASCII => \Spoofchecker::ASCII,
                SpoofLevel::SINGLE_SCRIPT_RESTRICTIVE => \Spoofchecker::SINGLE_SCRIPT_RESTRICTIVE,
                SpoofLevel::HIGHLY_RESTRICTIVE => \Spoofchecker::HIGHLY_RESTRICTIVE,
                SpoofLevel::MODERATELY_RESTRICTIVE => \Spoofchecker::MODERATELY_RESTRICTIVE,
                SpoofLevel::MINIMALLY_RESTRICTIVE => \Spoofchecker::MINIMALLY_RESTRICTIVE,
                SpoofLevel::UNRESTRICTIVE => \Spoofchecker::UNRESTRICTIVE,
            },
        );

        if ($checker->isSuspicious($value)) {
            return new Violation(
                code: NotSuspiciousViolationCode::SUSPICIOUS,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        return null;
    }
}
