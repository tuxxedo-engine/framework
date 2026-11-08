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

namespace Tuxxedo\Serializer\Converter;

use Tuxxedo\Serializer\Casting;
use Tuxxedo\Serializer\PropertyContextInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\ValueConverterInterface;

class EnumConverter implements ValueConverterInterface
{
    public function decode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        /** @var class-string<\UnitEnum> $enum */
        $enum = $property->typeName;

        if (\is_subclass_of($enum, \BackedEnum::class)) {
            $intBacked = (string) (new \ReflectionEnum($enum))->getBackingType() === 'int';

            if ($property->casting === Casting::LENIENT) {
                if ($intBacked && \is_string($value) && \preg_match('/^-?\d+$/', $value) === 1) {
                    $value = (int) $value;
                } elseif (!$intBacked && \is_int($value)) {
                    $value = (string) $value;
                }
            }

            $case = ($intBacked && \is_int($value)) || (!$intBacked && \is_string($value))
                ? $enum::tryFrom($value)
                : null;

            if ($case === null) {
                throw SerializerException::fromInvalidValue(
                    \sprintf(
                        'a value of %s',
                        $enum,
                    ),
                );
            }

            return $case;
        }

        if (\is_string($value)) {
            foreach ($enum::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        }

        throw SerializerException::fromInvalidValue(
            \sprintf(
                'a case of %s',
                $enum,
            ),
        );
    }

    public function encode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        throw SerializerException::fromInvalidValue('an enum case');
    }
}
