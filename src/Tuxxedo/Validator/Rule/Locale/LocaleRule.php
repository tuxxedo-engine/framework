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

namespace Tuxxedo\Validator\Rule\Locale;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class LocaleRule implements RuleInterface
{
    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if ($value === null || $value instanceof LocaleInterface) {
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

        try {
            Locale::parse($value);
        } catch (TranslationException) {
            return new Violation(
                code: LocaleViolationCode::INVALID,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        return null;
    }
}
