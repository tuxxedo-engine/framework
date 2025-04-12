<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Collections;

class IntCollection
{
    private function __construct()
    {
    }

    /**
     * @return Collection<int, int>
     */
    public static function from(
        int ...$values,
    ): Collection {
        return new Collection(\array_values($values));
    }

    /**
     * @return Collection<int, int>
     */
    public static function fromRange(
        int $start,
        int $end,
    ): Collection {
        return new Collection(
            \range($start, $end),
        );
    }

    /**
     * @param class-string<\BackedEnum> $enum
     * @return Collection<int, int>
     */
    public static function fromEnum(
        string $enum,
    ): Collection {
        return new Collection(
            \array_map(
                static fn(\BackedEnum $enum): int => (int) $enum->value,
                $enum::cases(),
            ),
        );
    }
}
