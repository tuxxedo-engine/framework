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

namespace Tuxxedo\Model\Attribute\Relation;

class RelationKeyTupleHasher
{
    private const string SEPARATOR = "\x1F";

    /**
     * @param non-empty-list<string|int|float|bool> $values
     */
    public static function hash(
        array $values,
    ): string {
        $parts = [];

        foreach ($values as $value) {
            $parts[] = \is_bool($value)
                ? ($value ? '1' : '0')
                : (string) $value;
        }

        return \implode(self::SEPARATOR, $parts);
    }
}
