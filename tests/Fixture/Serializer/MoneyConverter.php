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

namespace Fixture\Serializer;

use Tuxxedo\Serializer\PropertyContextInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\ValueConverterInterface;

class MoneyConverter implements ValueConverterInterface
{
    public function decode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        if (!\is_string($value) || \preg_match('/^(\d+)\.(\d{2}) ([A-Z]{3})$/', $value, $matches) !== 1) {
            throw SerializerException::fromInvalidValue('an amount like "12.50 EUR"');
        }

        return new Money(
            cents: (int) ($matches[1] . $matches[2]),
            currency: $matches[3],
        );
    }

    public function encode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        if (!$value instanceof Money) {
            throw SerializerException::fromInvalidValue('money');
        }

        return \sprintf(
            '%d.%02d %s',
            \intdiv($value->cents, 100),
            $value->cents % 100,
            $value->currency,
        );
    }
}
