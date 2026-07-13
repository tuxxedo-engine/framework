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

namespace Tuxxedo\Database\Query\Statement\Order;

use Tuxxedo\Database\SqlException;

enum OrderDirection: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';

    public static function fromInput(string $value): self
    {
        $normalized = \str_replace('_', ' ', $value);

        foreach (self::cases() as $case) {
            if (\strcasecmp($case->value, $normalized) === 0) {
                return $case;
            }
        }

        throw SqlException::fromUnknownOperator(
            value: $value,
            enum: self::class,
        );
    }
}
