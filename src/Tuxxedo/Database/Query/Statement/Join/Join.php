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

namespace Tuxxedo\Database\Query\Statement\Join;

use Tuxxedo\Database\Query\Statement\Condition\BetweenCondition;
use Tuxxedo\Database\Query\Statement\Condition\ColumnCondition;
use Tuxxedo\Database\Query\Statement\Condition\ColumnStartsWithCondition;
use Tuxxedo\Database\Query\Statement\Condition\ConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\ExistsCondition;
use Tuxxedo\Database\Query\Statement\Condition\FullTextCondition;
use Tuxxedo\Database\Query\Statement\Condition\GroupCondition;
use Tuxxedo\Database\Query\Statement\Condition\RawCondition;
use Tuxxedo\Database\Query\Statement\Condition\StartsWithCondition;
use Tuxxedo\Database\Query\Statement\Condition\SubqueryComparisonCondition;
use Tuxxedo\Database\Query\Statement\Condition\SubqueryCondition;

readonly class Join implements JoinInterface
{
    /**
     * @param list<ConditionInterface|BetweenCondition|ColumnCondition|RawCondition|SubqueryCondition|SubqueryComparisonCondition|ExistsCondition|GroupCondition|FullTextCondition|ColumnStartsWithCondition|StartsWithCondition> $conditions
     */
    public function __construct(
        public JoinType $type,
        public string $identifier,
        public ?string $alias = null,
        public array $conditions = [],
    ) {
    }
}
