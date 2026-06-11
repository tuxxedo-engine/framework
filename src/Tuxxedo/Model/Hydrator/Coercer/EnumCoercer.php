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

namespace Tuxxedo\Model\Hydrator\Coercer;

use Tuxxedo\Model\ModelException;

class EnumCoercer implements CoercerInterface
{
    /**
     * @param class-string<\UnitEnum> $enum
     */
    public function __construct(
        private readonly string $enum,
    ) {
    }

    public function hydrate(
        string|int|float|bool $value,
    ): \UnitEnum {
        if (\is_subclass_of($this->enum, \BackedEnum::class)) {
            return $this->hydrateBacked($value);
        }

        return $this->hydrateUnit($value);
    }

    public function dehydrate(
        mixed $value,
    ): string|int|float|bool {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        throw ModelException::fromCoercionFailure(
            coercerClass: static::class,
            expectedType: \UnitEnum::class,
            actualType: \get_debug_type($value),
        );
    }

    private function hydrateBacked(
        string|int|float|bool $value,
    ): \BackedEnum {
        if (!\is_int($value) && !\is_string($value)) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: 'int|string',
                actualType: \get_debug_type($value),
            );
        }

        /** @var class-string<\BackedEnum> $enumClass */
        $enumClass = $this->enum;
        $result = $enumClass::tryFrom($value);

        if ($result === null) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: \sprintf(
                    'value of %s',
                    $this->enum,
                ),
                actualType: \sprintf(
                    '"%s"',
                    $value,
                ),
            );
        }

        return $result;
    }

    private function hydrateUnit(
        string|int|float|bool $value,
    ): \UnitEnum {
        if (!\is_string($value)) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: 'string',
                actualType: \get_debug_type($value),
            );
        }

        foreach ($this->enum::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        throw ModelException::fromCoercionFailure(
            coercerClass: static::class,
            expectedType: \sprintf(
                'case of %s',
                $this->enum,
            ),
            actualType: \sprintf(
                '"%s"',
                $value,
            ),
        );
    }
}
