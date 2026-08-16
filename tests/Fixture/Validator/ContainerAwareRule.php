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

namespace Fixture\Validator;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class ContainerAwareRule implements RuleInterface
{
    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if (!\is_int($value)) {
            return null;
        }

        $checker = $container->resolve(ParityCheckerInterface::class);

        if ($checker->isEven($value)) {
            return null;
        }

        return new Violation(
            code: FixtureViolationCode::ODD_NUMBER,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new ContainerAwareRuleContext(
                received: $value,
            ),
        );
    }
}
