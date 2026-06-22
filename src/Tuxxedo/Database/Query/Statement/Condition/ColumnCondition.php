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

readonly class ColumnCondition implements ColumnConditionInterface
{
    public function __construct(
        public ConditionConjunction $conjunction,
        public string $identifier,
        public ConditionOperator $operator,
        public string $other,
    ) {
    }
}
