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

namespace Tuxxedo\Model\Attribute\Aggregate;

enum RelationAggregateFunction: string
{
    case COUNT = 'count';
    case SUM = 'sum';
    case AVG = 'avg';
    case MIN = 'min';
    case MAX = 'max';

    public function requiresColumn(): bool
    {
        return match ($this) {
            self::COUNT => false,
            self::SUM, self::AVG, self::MIN, self::MAX => true,
        };
    }

    public function sqlFunction(): string
    {
        return match ($this) {
            self::COUNT => 'COUNT',
            self::SUM => 'SUM',
            self::AVG => 'AVG',
            self::MIN => 'MIN',
            self::MAX => 'MAX',
        };
    }

    public function defaultAliasSuffix(
        ?string $column,
    ): string {
        return match ($this) {
            self::COUNT => 'count',
            self::SUM => 'sum_' . ($column ?? ''),
            self::AVG => 'avg_' . ($column ?? ''),
            self::MIN => 'min_' . ($column ?? ''),
            self::MAX => 'max_' . ($column ?? ''),
        };
    }
}
