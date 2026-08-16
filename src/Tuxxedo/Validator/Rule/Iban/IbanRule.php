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

namespace Tuxxedo\Validator\Rule\Iban;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class IbanRule implements RuleInterface
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

        $normalized = \strtoupper(\str_replace(' ', '', $value));

        if (\preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $normalized) !== 1) {
            return new Violation(
                code: IbanViolationCode::INVALID_FORMAT,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        if (!self::checksumMatches($normalized)) {
            return new Violation(
                code: IbanViolationCode::INVALID_CHECKSUM,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        return null;
    }

    private static function checksumMatches(
        string $iban,
    ): bool {
        $rearranged = \substr($iban, 4) . \substr($iban, 0, 4);
        $numeric = '';

        for ($i = 0, $length = \strlen($rearranged); $i < $length; $i++) {
            $character = $rearranged[$i];

            if (\preg_match('/^\d$/', $character) === 1) {
                $numeric .= $character;

                continue;
            }

            $numeric .= (string) (\ord($character) - \ord('A') + 10);
        }

        $remainder = 0;

        for ($i = 0, $length = \strlen($numeric); $i < $length; $i++) {
            $remainder = ($remainder * 10 + (int) $numeric[$i]) % 97;
        }

        return $remainder === 1;
    }
}
