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

use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;

class JoinCondition implements JoinConditionInterface
{
    public function __construct(
        private readonly WhereStatementInterface $statement,
    ) {
    }

    public function whereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        $this->statement->whereColumn($column, $other, $operator);

        return $this;
    }

    public function orWhereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        $this->statement->orWhereColumn($column, $other, $operator);

        return $this;
    }

    public function where(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        $this->statement->where($column, $value, $operator);

        return $this;
    }

    public function orWhere(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        $this->statement->orWhere($column, $value, $operator);

        return $this;
    }

    public function whereNull(
        string $column,
    ): static {
        $this->statement->whereNull($column);

        return $this;
    }

    public function whereNotNull(
        string $column,
    ): static {
        $this->statement->whereNotNull($column);

        return $this;
    }

    public function whereIn(
        string $column,
        array $values,
    ): static {
        $this->statement->whereIn($column, $values);

        return $this;
    }

    public function whereColumnStartsWith(
        string $column,
        string $prefixColumn,
    ): static {
        $this->statement->whereColumnStartsWith($column, $prefixColumn);

        return $this;
    }

    public function orWhereColumnStartsWith(
        string $column,
        string $prefixColumn,
    ): static {
        $this->statement->orWhereColumnStartsWith($column, $prefixColumn);

        return $this;
    }

    public function whereStartsWith(
        string $column,
        string $prefix,
    ): static {
        $this->statement->whereStartsWith($column, $prefix);

        return $this;
    }

    public function orWhereStartsWith(
        string $column,
        string $prefix,
    ): static {
        $this->statement->orWhereStartsWith($column, $prefix);

        return $this;
    }

    public function whereGroup(
        \Closure $callback,
    ): static {
        $this->statement->whereGroup(
            function (WhereStatementInterface $statement) use ($callback): void {
                $callback($this);
            },
        );

        return $this;
    }

    public function orWhereGroup(
        \Closure $callback,
    ): static {
        $this->statement->orWhereGroup(
            function (WhereStatementInterface $statement) use ($callback): void {
                $callback($this);
            },
        );

        return $this;
    }
}
