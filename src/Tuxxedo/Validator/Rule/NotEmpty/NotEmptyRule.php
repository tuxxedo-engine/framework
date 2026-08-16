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

namespace Tuxxedo\Validator\Rule\NotEmpty;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class NotEmptyRule implements RuleInterface
{
    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        $isEmpty = $value === null
            || (\is_string($value) && $value === '')
            || (\is_array($value) && \sizeof($value) === 0);

        if (!$isEmpty) {
            return null;
        }

        return new Violation(
            code: NotEmptyViolationCode::EMPTY_VALUE,
            propertyPath: $context->currentPath,
            invalidValue: $value,
        );
    }
}
