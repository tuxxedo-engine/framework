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

interface GroupConditionInterface
{
    public ConditionConjunction $conjunction {
        get;
    }

    public bool $negated {
        get;
    }

    /**
     * @var list<ConditionInterface|BetweenCondition|ColumnCondition|RawCondition|SubqueryCondition|GroupCondition>
     */
    public array $conditions {
        get;
    }
}
