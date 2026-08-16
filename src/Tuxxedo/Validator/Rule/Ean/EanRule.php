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

namespace Tuxxedo\Validator\Rule\Ean;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class EanRule implements RuleInterface
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

        $length = \strlen($value);

        if (($length !== 8 && $length !== 13) || \preg_match('/^\d+$/', $value) !== 1) {
            return new Violation(
                code: EanViolationCode::INVALID_FORMAT,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        if (!self::checksumMatches($value)) {
            return new Violation(
                code: EanViolationCode::INVALID_CHECKSUM,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        return null;
    }

    private static function checksumMatches(
        string $ean,
    ): bool {
        $length = \strlen($ean);
        $checkDigit = (int) $ean[$length - 1];
        $sum = 0;

        for ($i = 0; $i < $length - 1; $i++) {
            $digit = (int) $ean[$i];
            $weight = ($length - $i) % 2 === 0 ? 3 : 1;
            $sum += $digit * $weight;
        }

        $computed = (10 - ($sum % 10)) % 10;

        return $computed === $checkDigit;
    }
}
