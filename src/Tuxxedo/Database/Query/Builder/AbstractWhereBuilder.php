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

namespace Tuxxedo\Database\Query\Builder;

abstract class AbstractWhereBuilder extends AbstractBuilder implements WhereBuilderInterface
{
    /**
     * @var ConditionInterface[]
     */
    protected array $conditions = [];

    /**
     * @var JoinInterface[]
     */
    protected array $joins = [];

    protected function generateWhereSql(): string
    {
        // @todo Implement

        return '';
    }

    public function hasConstraints(): bool
    {
        return $this->hasConditionConstraints() || $this->hasJoinConstraints();
    }

    public function hasConditionConstraints(): bool
    {
        return \sizeof($this->conditions) > 0;
    }

    public function hasJoinConstraints(): bool
    {
        return \sizeof($this->joins) > 0;
    }

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function where(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::from($operator);
        }

        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $value;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $this->connection->dialect->identifier($column),
            operator: $operator,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function orWhere(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::from($operator);
        }

        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $value;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $this->connection->dialect->identifier($column),
            operator: $operator,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    public function whereNull(
        string $column,
    ): static {
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $this->connection->dialect->identifier($column),
            operator: ConditionOperator::IS_NULL,
        );

        return $this;
    }

    public function whereNotNull(
        string $column,
    ): static {
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $this->connection->dialect->identifier($column),
            operator: ConditionOperator::IS_NOT_NULL,
        );

        return $this;
    }

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function whereIn(
        string $column,
        array $values,
    ): static {
        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $values;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $this->connection->dialect->identifier($column),
            operator: ConditionOperator::IN,
            parameter: ':' . $parameterKey . '[]',
        );

        return $this;
    }

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function whereNotIn(
        string $column,
        array $values,
    ): static {
        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $values;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $this->connection->dialect->identifier($column),
            operator: ConditionOperator::NOT_IN,
            parameter: ':' . $parameterKey . '[]',
        );

        return $this;
    }

    public function innerJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = JoinOperator::from($operator);
        }

        $this->joins[] = new Join(
            type: JoinType::INNER,
            identifier: $this->connection->dialect->identifier($table),
            first: $this->connection->dialect->identifier($first),
            second: $this->connection->dialect->identifier($second),
            operator: $operator,
        );

        return $this;
    }

    public function leftJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = JoinOperator::from($operator);
        }

        $this->joins[] = new Join(
            type: JoinType::LEFT,
            identifier: $this->connection->dialect->identifier($table),
            first: $this->connection->dialect->identifier($first),
            second: $this->connection->dialect->identifier($second),
            operator: $operator,
        );

        return $this;
    }

    public function rightJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = JoinOperator::from($operator);
        }

        $this->joins[] = new Join(
            type: JoinType::RIGHT,
            identifier: $this->connection->dialect->identifier($table),
            first: $this->connection->dialect->identifier($first),
            second: $this->connection->dialect->identifier($second),
            operator: $operator,
        );

        return $this;
    }

    public function crossJoin(
        string $table,
    ): static {
        $this->joins[] = new Join(
            type: JoinType::CROSS,
            identifier: $this->connection->dialect->identifier($table),
            first: '',
            second: '',
            operator: null,
        );

        return $this;
    }
}
