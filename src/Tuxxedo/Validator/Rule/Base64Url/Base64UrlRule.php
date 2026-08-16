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

namespace Tuxxedo\Validator\Rule\Base64Url;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class Base64UrlRule implements RuleInterface
{
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

        if (\preg_match('/^[A-Za-z0-9_-]*$/', $value) !== 1) {
            return new Violation(
                code: Base64UrlViolationCode::INVALID_FORMAT,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        $padded = $value . \str_repeat('=', (4 - \strlen($value) % 4) % 4);
        $standard = \strtr($padded, '-_', '+/');
        $decoded = \base64_decode($standard, true);

        if ($decoded === false) {
            // @codeCoverageIgnoreStart
            return new Violation(
                code: Base64UrlViolationCode::INVALID_FORMAT,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
            // @codeCoverageIgnoreEnd
        }

        return null;
    }
}
