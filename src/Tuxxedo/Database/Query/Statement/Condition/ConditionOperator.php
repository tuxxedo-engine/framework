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

namespace Tuxxedo\Database\Query\Statement\Condition;

use Tuxxedo\Database\SqlException;

enum ConditionOperator: string
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case LESS_THAN = '<';
    case LESS_THAN_OR_EQUAL = '<=';
    case GREATER_THAN = '>';
    case GREATER_THAN_OR_EQUAL = '>=';
    case LIKE = 'LIKE';
    case NOT_LIKE = 'NOT LIKE';
    case IN = 'IN';
    case NOT_IN = 'NOT IN';
    case IS_NULL = 'IS NULL';
    case IS_NOT_NULL  = 'IS NOT NULL';

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
