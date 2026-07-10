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

namespace Tuxxedo\Reflection;

class EnumHydrator
{
    /**
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enumClass
     * @return (T&\UnitEnum)|null
     */
    public static function hydrate(
        string $enumClass,
        string $value,
    ): ?\UnitEnum {
        if (\is_subclass_of($enumClass, \BackedEnum::class)) {
            return self::hydrateBacked(
                enumClass: $enumClass,
                value: $value,
            );
        }

        foreach ($enumClass::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enumClass
     * @return (T&\UnitEnum)|null
     */
    public static function hydrateCaseInsensitive(
        string $enumClass,
        string $value,
    ): ?\UnitEnum {
        if (\is_subclass_of($enumClass, \BackedEnum::class)) {
            return self::hydrateBacked(
                enumClass: $enumClass,
                value: $value,
            );
        }

        foreach ($enumClass::cases() as $case) {
            if (\strcasecmp($case->name, $value) === 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enumClass
     * @return (T&\BackedEnum)|null
     */
    private static function hydrateBacked(
        string $enumClass,
        string $value,
    ): ?\BackedEnum {
        $backingType = (new \ReflectionEnum($enumClass))->getBackingType();

        if (!$backingType instanceof \ReflectionNamedType) {
            return null;
        }

        if (
            $backingType->getName() === 'int' &&
            \preg_match('/\A-?\d+\z/', $value) === 1
        ) {
            return $enumClass::tryFrom((int) $value);
        }

        if ($backingType->getName() === 'string') {
            return $enumClass::tryFrom($value);
        }

        return null;
    }
}
