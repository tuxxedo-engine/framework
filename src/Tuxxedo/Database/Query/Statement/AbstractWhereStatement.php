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

namespace Tuxxedo\Database\Query\Statement;

use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Statement\Condition\BetweenCondition;
use Tuxxedo\Database\Query\Statement\Condition\BetweenConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\BetweenOperator;
use Tuxxedo\Database\Query\Statement\Condition\Condition;
use Tuxxedo\Database\Query\Statement\Condition\ConditionConjunction;
use Tuxxedo\Database\Query\Statement\Condition\ConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Join\Join;
use Tuxxedo\Database\Query\Statement\Join\JoinInterface;
use Tuxxedo\Database\Query\Statement\Join\JoinOperator;
use Tuxxedo\Database\Query\Statement\Join\JoinType;

abstract class AbstractWhereStatement extends AbstractStatement implements WhereStatementInterface
{
    /**
     * @var ConditionInterface[]|BetweenCondition[]
     */
    protected array $conditions = [];

    /**
     * @var JoinInterface[]
     */
    protected array $joins = [];

    protected function generateWhereSql(
        DialectInterface $dialect,
    ): string {
        $sql = '';

        foreach ($this->joins as $join) {
            $type = match ($join->type) {
                JoinType::INNER => 'INNER JOIN',
                JoinType::LEFT  => 'LEFT JOIN',
                JoinType::RIGHT => 'RIGHT JOIN',
                JoinType::CROSS => 'CROSS JOIN',
            };

            if ($join->type === JoinType::CROSS || $join->operator === null) {
                $sql .= \sprintf(
                    ' %s %s',
                    $type,
                    $dialect->identifier($join->identifier),
                );

                continue;
            }

            $sql .= \sprintf(
                ' %s %s ON %s %s %s',
                $type,
                $dialect->identifier($join->identifier),
                $dialect->qualifiedIdentifier($join->first),
                $join->operator->value,
                $dialect->qualifiedIdentifier($join->second),
            );
        }

        foreach ($this->conditions as $index => $condition) {
            $keyword = $index === 0
                ? 'WHERE' :
                $condition->conjunction->name;

            if ($condition instanceof BetweenConditionInterface) {
                $sql .= \sprintf(
                    ' %s %s %s %s AND %s',
                    $keyword,
                    $dialect->qualifiedIdentifier($condition->identifier),
                    $condition->operator->value,
                    $condition->from,
                    $condition->to,
                );

                continue;
            }

            if (
                $condition->operator === ConditionOperator::IS_NULL ||
                $condition->operator === ConditionOperator::IS_NOT_NULL
            ) {
                $sql .= \sprintf(
                    ' %s %s %s',
                    $keyword,
                    $dialect->qualifiedIdentifier($condition->identifier),
                    $condition->operator->value,
                );

                continue;
            }

            if (
                $condition->operator === ConditionOperator::IN ||
                $condition->operator === ConditionOperator::NOT_IN
            ) {
                $sql .= \sprintf(
                    ' %s %s %s (%s)',
                    $keyword,
                    $dialect->qualifiedIdentifier($condition->identifier),
                    $condition->operator->value,
                    $condition->parameter,
                );

                continue;
            }

            $sql .= \sprintf(
                ' %s %s %s %s',
                $keyword,
                $dialect->qualifiedIdentifier($condition->identifier),
                $condition->operator->value,
                $condition->parameter,
            );
        }

        return $sql;
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
            identifier: $column,
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
            identifier: $column,
            operator: $operator,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    public function orWhereNull(
        string $column,
    ): static {
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::IS_NULL,
        );

        return $this;
    }

    public function orWhereNotNull(
        string $column,
    ): static {
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::IS_NOT_NULL,
        );

        return $this;
    }

    public function whereNull(
        string $column,
    ): static {
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: ConditionOperator::IS_NULL,
        );

        return $this;
    }

    public function whereNotNull(
        string $column,
    ): static {
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
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
            identifier: $column,
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
            identifier: $column,
            operator: ConditionOperator::NOT_IN,
            parameter: ':' . $parameterKey . '[]',
        );

        return $this;
    }

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function orWhereIn(
        string $column,
        array $values,
    ): static {
        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $values;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::IN,
            parameter: ':' . $parameterKey . '[]',
        );

        return $this;
    }

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function orWhereNotIn(
        string $column,
        array $values,
    ): static {
        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $values;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
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
            identifier: $table,
            first: $first,
            second: $second,
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
            identifier: $table,
            first: $first,
            second: $second,
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
            identifier: $table,
            first: $first,
            second: $second,
            operator: $operator,
        );

        return $this;
    }

    public function crossJoin(
        string $table,
    ): static {
        $this->joins[] = new Join(
            type: JoinType::CROSS,
            identifier: $table,
            first: '',
            second: '',
            operator: null,
        );

        return $this;
    }

    public function whereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        $parameterKey = 'between_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->conditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: BetweenOperator::BETWEEN,
            from: ':' . $parameterKey . '_from',
            to: ':' . $parameterKey . '_to',
        );

        return $this;
    }

    public function whereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        $parameterKey = 'between_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->conditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: BetweenOperator::NOT_BETWEEN,
            from: ':' . $parameterKey . '_from',
            to: ':' . $parameterKey . '_to',
        );

        return $this;
    }

    public function orWhereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        $parameterKey = 'between_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->conditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: BetweenOperator::BETWEEN,
            from: ':' . $parameterKey . '_from',
            to: ':' . $parameterKey . '_to',
        );

        return $this;
    }

    public function orWhereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        $parameterKey = 'between_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->conditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: BetweenOperator::NOT_BETWEEN,
            from: ':' . $parameterKey . '_from',
            to: ':' . $parameterKey . '_to',
        );

        return $this;
    }

    public function whereLike(
        string $column,
        string $pattern,
    ): static {
        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $pattern;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: ConditionOperator::LIKE,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    public function whereNotLike(
        string $column,
        string $pattern,
    ): static {
        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $pattern;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: ConditionOperator::NOT_LIKE,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    public function orWhereLike(
        string $column,
        string $pattern,
    ): static {
        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $pattern;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::LIKE,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    public function orWhereNotLike(
        string $column,
        string $pattern,
    ): static {
        $parameterKey = 'where_' . \sizeof($this->conditions);

        $this->parameters[$parameterKey] = $pattern;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::NOT_LIKE,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }
}
