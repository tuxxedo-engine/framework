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
use Tuxxedo\Database\Query\Statement\Condition\ColumnCondition;
use Tuxxedo\Database\Query\Statement\Condition\ColumnConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\Condition;
use Tuxxedo\Database\Query\Statement\Condition\ConditionConjunction;
use Tuxxedo\Database\Query\Statement\Condition\ConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Condition\RawCondition;
use Tuxxedo\Database\Query\Statement\Condition\RawConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\SubqueryCondition;
use Tuxxedo\Database\Query\Statement\Condition\SubqueryConditionInterface;
use Tuxxedo\Database\Query\Statement\Join\Join;
use Tuxxedo\Database\Query\Statement\Join\JoinInterface;
use Tuxxedo\Database\Query\Statement\Join\JoinOperator;
use Tuxxedo\Database\Query\Statement\Join\JoinType;
use Tuxxedo\Database\SqlException;

abstract class AbstractWhereStatement extends AbstractStatement implements WhereStatementInterface
{
    /**
     * @var list<ConditionInterface|BetweenCondition|ColumnCondition|RawCondition|SubqueryCondition>
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

            if ($condition instanceof ColumnConditionInterface) {
                $sql .= \sprintf(
                    ' %s %s %s %s',
                    $keyword,
                    $dialect->qualifiedIdentifier($condition->identifier),
                    $condition->operator->value,
                    $dialect->qualifiedIdentifier($condition->other),
                );

                continue;
            }

            if ($condition instanceof RawConditionInterface) {
                $sql .= \sprintf(
                    ' %s %s',
                    $keyword,
                    $condition->sql,
                );

                continue;
            }

            if ($condition instanceof SubqueryConditionInterface) {
                $sql .= \sprintf(
                    ' %s %s %s (%s)',
                    $keyword,
                    $dialect->qualifiedIdentifier($condition->identifier),
                    $condition->operator->value,
                    $this->renderSubquery($condition->subquery, $index, $dialect),
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
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    public function whereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        if ($values instanceof SelectStatementInterface) {
            $this->conditions[] = $this->buildSubqueryCondition(
                column: $column,
                operator: ConditionOperator::IN,
                conjunction: ConditionConjunction::AND,
                values: $values,
            );

            return $this;
        }

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
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    public function whereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        if ($values instanceof SelectStatementInterface) {
            $this->conditions[] = $this->buildSubqueryCondition(
                column: $column,
                operator: ConditionOperator::NOT_IN,
                conjunction: ConditionConjunction::AND,
                values: $values,
            );

            return $this;
        }

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
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    public function orWhereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        if ($values instanceof SelectStatementInterface) {
            $this->conditions[] = $this->buildSubqueryCondition(
                column: $column,
                operator: ConditionOperator::IN,
                conjunction: ConditionConjunction::OR,
                values: $values,
            );

            return $this;
        }

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
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    public function orWhereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        if ($values instanceof SelectStatementInterface) {
            $this->conditions[] = $this->buildSubqueryCondition(
                column: $column,
                operator: ConditionOperator::NOT_IN,
                conjunction: ConditionConjunction::OR,
                values: $values,
            );

            return $this;
        }

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

    /**
     * @throws SqlException
     */
    private function buildSubqueryCondition(
        string $column,
        ConditionOperator $operator,
        ConditionConjunction $conjunction,
        SelectStatementInterface $values,
    ): SubqueryCondition {
        if (!$values instanceof AbstractStatement) {
            throw SqlException::fromSubqueryStatementMustExtendAbstractStatement(
                actualType: \get_debug_type($values),
            );
        }

        return new SubqueryCondition(
            conjunction: $conjunction,
            identifier: $column,
            operator: $operator,
            subquery: $values,
        );
    }

    private function renderSubquery(
        AbstractStatement $subquery,
        int $index,
        DialectInterface $dialect,
    ): string {
        $sql = $subquery->generateSql($dialect);
        $prefix = 'subq_' . $index . '_';

        foreach ($subquery->parameters as $key => $value) {
            $stringKey = (string) $key;
            $this->parameters[$prefix . $stringKey] = $value;
            $sql = \preg_replace(
                pattern: '/:' . \preg_quote($stringKey, '/') . '\b/',
                replacement: ':' . $prefix . $stringKey,
                subject: $sql,
            ) ?? $sql;
        }

        return $sql;
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

    public function whereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::from($operator);
        }

        $this->conditions[] = new ColumnCondition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: $operator,
            other: $other,
        );

        return $this;
    }

    public function orWhereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::from($operator);
        }

        $this->conditions[] = new ColumnCondition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: $operator,
            other: $other,
        );

        return $this;
    }

    /**
     * @param array<string, string|int|float|bool|null> $bindings
     */
    public function whereRaw(
        string $sql,
        array $bindings = [],
    ): static {
        $prefix = 'raw_' . \sizeof($this->conditions) . '_';

        $rewritten = \preg_replace_callback(
            '/(?<!:):([a-zA-Z_][a-zA-Z0-9_]*)/',
            static fn (array $matches): string => isset($bindings[$matches[1]])
                ? ':' . $prefix . $matches[1]
                : $matches[0],
            $sql,
        );

        foreach ($bindings as $key => $value) {
            $this->parameters[$prefix . $key] = $value;
        }

        $this->conditions[] = new RawCondition(
            conjunction: ConditionConjunction::AND,
            sql: $rewritten ?? $sql,
        );

        return $this;
    }
}
