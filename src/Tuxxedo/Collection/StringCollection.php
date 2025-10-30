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

namespace Tuxxedo\Collection;

class StringCollection
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @return Collection<int, string>
     */
    public static function from(
        string ...$values,
    ): Collection {
        return new Collection(\array_values($values));
    }

    /**
     * @param class-string<\BackedEnum> $enum
     * @return Collection<int, string>
     */
    public static function fromEnum(
        string $enum,
    ): Collection {
        return new Collection(
            \array_map(
                static fn (\BackedEnum $enum): string => (string) $enum->value,
                $enum::cases(),
            ),
        );
    }
}
