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

namespace Tuxxedo\Database\Query\Statement\Aggregate;

enum AggregateFunction: string
{
    case COUNT = 'COUNT';
    case SUM = 'SUM';
    case AVG = 'AVG';
    case MIN = 'MIN';
    case MAX = 'MAX';

    public function requiresColumn(): bool
    {
        return match ($this) {
            self::COUNT => false,
            self::SUM, self::AVG, self::MIN, self::MAX => true,
        };
    }
}
