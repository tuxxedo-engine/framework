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

namespace Tuxxedo\Database\Query\Statement\Window;

enum WindowFunction: string
{
    case ROW_NUMBER = 'ROW_NUMBER';
    case RANK = 'RANK';
    case DENSE_RANK = 'DENSE_RANK';
    case PERCENT_RANK = 'PERCENT_RANK';
    case CUME_DIST = 'CUME_DIST';
    case NTILE = 'NTILE';
    case LAG = 'LAG';
    case LEAD = 'LEAD';
    case FIRST_VALUE = 'FIRST_VALUE';
    case LAST_VALUE = 'LAST_VALUE';
    case NTH_VALUE = 'NTH_VALUE';

    public function takesColumn(): bool
    {
        return match ($this) {
            self::LAG, self::LEAD, self::FIRST_VALUE, self::LAST_VALUE, self::NTH_VALUE => true,
            self::ROW_NUMBER, self::RANK, self::DENSE_RANK, self::PERCENT_RANK, self::CUME_DIST, self::NTILE => false,
        };
    }

    public function requiresArgument(): bool
    {
        return match ($this) {
            self::NTILE, self::NTH_VALUE => true,
            self::ROW_NUMBER, self::RANK, self::DENSE_RANK, self::PERCENT_RANK, self::CUME_DIST, self::LAG, self::LEAD, self::FIRST_VALUE, self::LAST_VALUE => false,
        };
    }

    public function takesArgument(): bool
    {
        return match ($this) {
            self::NTILE, self::NTH_VALUE, self::LAG, self::LEAD => true,
            self::ROW_NUMBER, self::RANK, self::DENSE_RANK, self::PERCENT_RANK, self::CUME_DIST, self::FIRST_VALUE, self::LAST_VALUE => false,
        };
    }

    public function takesDefault(): bool
    {
        return match ($this) {
            self::LAG, self::LEAD => true,
            self::ROW_NUMBER, self::RANK, self::DENSE_RANK, self::PERCENT_RANK, self::CUME_DIST, self::NTILE, self::FIRST_VALUE, self::LAST_VALUE, self::NTH_VALUE => false,
        };
    }

    public function minimumArgument(): int
    {
        return match ($this) {
            self::NTILE, self::NTH_VALUE => 1,
            self::ROW_NUMBER, self::RANK, self::DENSE_RANK, self::PERCENT_RANK, self::CUME_DIST, self::LAG, self::LEAD, self::FIRST_VALUE, self::LAST_VALUE => 0,
        };
    }
}
