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

#[\Attribute(flags: \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
class YesNo implements ValueConverterInterface
{
    public function decode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        return match ($value) {
            'Y' => true,
            'N' => false,
            default => throw SerializerException::fromInvalidValue('"Y" or "N"'),
        };
    }

    public function encode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        return $value === true
            ? 'Y'
            : 'N';
    }
}
