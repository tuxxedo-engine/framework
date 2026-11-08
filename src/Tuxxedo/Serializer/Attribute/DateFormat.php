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

namespace Tuxxedo\Serializer\Attribute;

use Tuxxedo\Serializer\Converter\TemporalConverter;
use Tuxxedo\Serializer\PropertyContextInterface;
use Tuxxedo\Serializer\ValueConverterInterface;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
class DateFormat implements ValueConverterInterface
{
    public function __construct(
        public readonly string $format,
    ) {
    }

    public function decode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        return (new TemporalConverter($this->format))->decode($value, $property);
    }

    public function encode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        return (new TemporalConverter($this->format))->encode($value, $property);
    }
}
