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

namespace Tuxxedo\Validator\Rule\TimeZoneCountry;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Intl\Temporal\TimeZoneListInterface;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Temporal\TimeZoneInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\ValidatorException;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class TimeZoneCountryRule implements RuleInterface
{
    public readonly string $country;

    /**
     * @throws ValidatorException
     */
    public function __construct(
        string $country,
    ) {
        $code = \strtoupper($country);

        if (\preg_match('/^[A-Z]{2}$/', $code) !== 1) {
            throw ValidatorException::fromInvalidCountryCode(
                country: $country,
            );
        }

        $this->country = $code;
    }

    /**
     * @throws TranslationException
     */
    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof TimeZoneInterface && !\is_string($value)) {
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

        $name = $value instanceof TimeZoneInterface
            ? $value->name
            : $value;

        if (!\in_array($name, $container->resolve(TimeZoneListInterface::class)->names($this->country), true)) {
            return new Violation(
                code: TimeZoneCountryViolationCode::NOT_IN_COUNTRY,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new TimeZoneCountryViolationContext(
                    country: $this->country,
                ),
            );
        }

        return null;
    }
}
