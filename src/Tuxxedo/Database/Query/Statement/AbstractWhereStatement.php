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

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\FullText\FullText;
use Tuxxedo\Database\Query\Statement\Condition\BetweenCondition;
use Tuxxedo\Database\Query\Statement\Condition\BetweenConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\BetweenOperator;
use Tuxxedo\Database\Query\Statement\Condition\ColumnCondition;
use Tuxxedo\Database\Query\Statement\Condition\ColumnConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\Condition;
use Tuxxedo\Database\Query\Statement\Condition\ConditionConjunction;
use Tuxxedo\Database\Query\Statement\Condition\ConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Condition\ExistsCondition;
use Tuxxedo\Database\Query\Statement\Condition\ExistsConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\FullTextCondition;
use Tuxxedo\Database\Query\Statement\Condition\FullTextConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\GroupCondition;
use Tuxxedo\Database\Query\Statement\Condition\GroupConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\RawCondition;
use Tuxxedo\Database\Query\Statement\Condition\RawConditionInterface;
use Tuxxedo\Database\Query\Statement\Condition\SubqueryComparisonCondition;
use Tuxxedo\Database\Query\Statement\Condition\SubqueryComparisonConditionInterface;
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
     * @var list<ConditionInterface|BetweenCondition|ColumnCondition|RawCondition|SubqueryCondition|SubqueryComparisonCondition|ExistsCondition|GroupCondition|FullTextCondition>
     */
    protected array $conditions = [];

    /**
     * @var JoinInterface[]
     */
    protected array $joins = [];

    /**
     * @var \WeakMap<ConnectionInterface, array<string, true>>|null
     */
    private static ?\WeakMap $verifiedFullTextIndexes = null;

    private int $subqueryCounter = 0;

    protected int $parameterCounter = 0;

    protected function nextParameterOffset(): int
    {
        return $this->parameterCounter++;
    }

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

        if (\sizeof($this->conditions) > 0) {
            $this->subqueryCounter = 0;
            $sql .= ' WHERE' . $this->renderConditions($this->conditions, $dialect);
        }

        return $sql;
    }

    /**
     * @param list<ConditionInterface|BetweenCondition|ColumnCondition|RawCondition|SubqueryCondition|SubqueryComparisonCondition|ExistsCondition|GroupCondition|FullTextCondition> $conditions
     */
    private function renderConditions(
        array $conditions,
        DialectInterface $dialect,
    ): string {
        $sql = '';

        foreach ($conditions as $index => $condition) {
            if ($index > 0) {
                $sql .= ' ' . $condition->conjunction->name;
            }

            $sql .= $this->renderConditionBody($condition, $dialect);
        }

        return $sql;
    }

    private function renderConditionBody(
        ConditionInterface|BetweenCondition|ColumnCondition|RawCondition|SubqueryCondition|SubqueryComparisonCondition|ExistsCondition|GroupCondition|FullTextCondition $condition,
        DialectInterface $dialect,
    ): string {
        if ($condition instanceof BetweenConditionInterface) {
            return \sprintf(
                ' %s %s %s AND %s',
                $dialect->qualifiedIdentifier($condition->identifier),
                $condition->operator->value,
                $condition->from,
                $condition->to,
            );
        }

        if ($condition instanceof ColumnConditionInterface) {
            return \sprintf(
                ' %s %s %s',
                $dialect->qualifiedIdentifier($condition->identifier),
                $dialect->conditionOperator($condition->operator),
                $dialect->qualifiedIdentifier($condition->other),
            );
        }

        if ($condition instanceof RawConditionInterface) {
            return ' ' . $condition->sql;
        }

        if ($condition instanceof SubqueryConditionInterface) {
            return \sprintf(
                ' %s %s (%s)',
                $dialect->qualifiedIdentifier($condition->identifier),
                $dialect->conditionOperator($condition->operator),
                $this->renderSubquery(
                    $condition->subquery,
                    $this->subqueryCounter++,
                    $dialect,
                ),
            );
        }

        if ($condition instanceof SubqueryComparisonConditionInterface) {
            return \sprintf(
                ' (%s) %s %s',
                $this->renderSubquery(
                    $condition->subquery,
                    $this->subqueryCounter++,
                    $dialect,
                ),
                $dialect->conditionOperator($condition->operator),
                $condition->parameter,
            );
        }

        if ($condition instanceof ExistsConditionInterface) {
            return ($condition->negated ? ' NOT EXISTS (' : ' EXISTS (') .
                $this->renderSubquery(
                    $condition->subquery,
                    $this->subqueryCounter++,
                    $dialect,
                ) .
                ')';
        }

        if ($condition instanceof FullTextConditionInterface) {
            return ' ' . $dialect->fullTextCondition(
                columns: $condition->columns,
                terms: $condition->terms,
            );
        }

        if ($condition instanceof GroupConditionInterface) {
            return ($condition->negated ? ' NOT (' : ' (') . \ltrim($this->renderConditions($condition->conditions, $dialect)) . ')';
        }

        if (
            $condition->operator === ConditionOperator::IS_NULL ||
            $condition->operator === ConditionOperator::IS_NOT_NULL
        ) {
            return \sprintf(
                ' %s %s',
                $dialect->qualifiedIdentifier($condition->identifier),
                $dialect->conditionOperator($condition->operator),
            );
        }

        if (
            $condition->operator === ConditionOperator::IN ||
            $condition->operator === ConditionOperator::NOT_IN
        ) {
            return \sprintf(
                ' %s %s (%s)',
                $dialect->qualifiedIdentifier($condition->identifier),
                $dialect->conditionOperator($condition->operator),
                $condition->parameter,
            );
        }

        return \sprintf(
            ' %s %s %s',
            $dialect->qualifiedIdentifier($condition->identifier),
            $dialect->conditionOperator($condition->operator),
            $condition->parameter,
        );
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
     * @param SelectStatementInterface|string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function where(
        string $column,
        SelectStatementInterface|string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::fromInput($operator);
        }

        if ($value instanceof SelectStatementInterface) {
            $this->conditions[] = $this->buildSubqueryCondition(
                column: $column,
                operator: $operator,
                conjunction: ConditionConjunction::AND,
                values: $value,
            );

            return $this;
        }

        $parameterKey = 'where_' . $this->nextParameterOffset();

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
     * @param SelectStatementInterface|string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function orWhere(
        string $column,
        SelectStatementInterface|string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::fromInput($operator);
        }

        if ($value instanceof SelectStatementInterface) {
            $this->conditions[] = $this->buildSubqueryCondition(
                column: $column,
                operator: $operator,
                conjunction: ConditionConjunction::OR,
                values: $value,
            );

            return $this;
        }

        $parameterKey = 'where_' . $this->nextParameterOffset();

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

        $parameterKey = 'where_' . $this->nextParameterOffset();

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

        $parameterKey = 'where_' . $this->nextParameterOffset();

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

        $parameterKey = 'where_' . $this->nextParameterOffset();

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

        $parameterKey = 'where_' . $this->nextParameterOffset();

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
            // @codeCoverageIgnoreStart
            throw SqlException::fromSubqueryStatementMustExtendAbstractStatement(
                actualType: \get_debug_type($values),
            );
            // @codeCoverageIgnoreEnd
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
        return $this->renderSubqueryWithPrefix(
            subquery: $subquery,
            prefix: 'subq_' . $index . '_',
            dialect: $dialect,
        );
    }

    protected function renderSubqueryWithPrefix(
        AbstractStatement $subquery,
        string $prefix,
        DialectInterface $dialect,
    ): string {
        $sql = $subquery->generateSql($dialect);
        $keys = [];

        foreach ($subquery->parameters as $key => $value) {
            $stringKey = (string) $key;
            $keys[$stringKey] = true;
            $this->parameters[$prefix . $stringKey] = $value;
        }

        return \preg_replace_callback(
            pattern: '/:([A-Za-z_][A-Za-z0-9_]*)/',
            callback: static fn (array $match): string => isset($keys[$match[1]])
                ? ':' . $prefix . $match[1]
                : $match[0],
            subject: $sql,
        ) ?? $sql;
    }

    public function innerJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = JoinOperator::fromInput($operator);
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
            $operator = JoinOperator::fromInput($operator);
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
            $operator = JoinOperator::fromInput($operator);
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
        BetweenOperator|string $operator = BetweenOperator::BETWEEN,
    ): static {
        if (\is_string($operator)) {
            $operator = BetweenOperator::fromInput($operator);
        }

        $parameterKey = 'between_' . $this->nextParameterOffset();

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->conditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::AND,
            identifier: $column,
            operator: $operator,
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
        return $this->whereBetween(
            column: $column,
            from: $from,
            to: $to,
            operator: BetweenOperator::NOT_BETWEEN,
        );
    }

    public function orWhereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
        BetweenOperator|string $operator = BetweenOperator::BETWEEN,
    ): static {
        if (\is_string($operator)) {
            $operator = BetweenOperator::fromInput($operator);
        }

        $parameterKey = 'between_' . $this->nextParameterOffset();

        $this->parameters[$parameterKey . '_from'] = $from;
        $this->parameters[$parameterKey . '_to'] = $to;
        $this->conditions[] = new BetweenCondition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: $operator,
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
        return $this->orWhereBetween(
            column: $column,
            from: $from,
            to: $to,
            operator: BetweenOperator::NOT_BETWEEN,
        );
    }

    public function whereLike(
        string $column,
        string $pattern,
    ): static {
        $parameterKey = 'where_' . $this->nextParameterOffset();

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
        $parameterKey = 'where_' . $this->nextParameterOffset();

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
        $parameterKey = 'where_' . $this->nextParameterOffset();

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
        $parameterKey = 'where_' . $this->nextParameterOffset();

        $this->parameters[$parameterKey] = $pattern;
        $this->conditions[] = new Condition(
            conjunction: ConditionConjunction::OR,
            identifier: $column,
            operator: ConditionOperator::NOT_LIKE,
            parameter: ':' . $parameterKey,
        );

        return $this;
    }

    public function whereFullText(
        array $columns,
        string $search,
    ): static {
        return $this->addFullTextCondition(
            columns: $columns,
            search: $search,
            conjunction: ConditionConjunction::AND,
        );
    }

    public function orWhereFullText(
        array $columns,
        string $search,
    ): static {
        return $this->addFullTextCondition(
            columns: $columns,
            search: $search,
            conjunction: ConditionConjunction::OR,
        );
    }

    public function whereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        if (\is_string($operator)) {
            $operator = ConditionOperator::fromInput($operator);
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
            $operator = ConditionOperator::fromInput($operator);
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
        $prefix = 'raw_' . $this->nextParameterOffset() . '_';

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

    public function whereSubquery(
        SelectStatementInterface $subquery,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        $this->conditions[] = $this->buildSubqueryComparison(
            subquery: $subquery,
            value: $value,
            operator: $operator,
            conjunction: ConditionConjunction::AND,
        );

        return $this;
    }

    public function orWhereSubquery(
        SelectStatementInterface $subquery,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        $this->conditions[] = $this->buildSubqueryComparison(
            subquery: $subquery,
            value: $value,
            operator: $operator,
            conjunction: ConditionConjunction::OR,
        );

        return $this;
    }

    public function whereExists(
        SelectStatementInterface $subquery,
    ): static {
        $this->conditions[] = $this->buildExists(
            subquery: $subquery,
            conjunction: ConditionConjunction::AND,
            negated: false,
        );

        return $this;
    }

    public function whereNotExists(
        SelectStatementInterface $subquery,
    ): static {
        $this->conditions[] = $this->buildExists(
            subquery: $subquery,
            conjunction: ConditionConjunction::AND,
            negated: true,
        );

        return $this;
    }

    public function orWhereExists(
        SelectStatementInterface $subquery,
    ): static {
        $this->conditions[] = $this->buildExists(
            subquery: $subquery,
            conjunction: ConditionConjunction::OR,
            negated: false,
        );

        return $this;
    }

    public function orWhereNotExists(
        SelectStatementInterface $subquery,
    ): static {
        $this->conditions[] = $this->buildExists(
            subquery: $subquery,
            conjunction: ConditionConjunction::OR,
            negated: true,
        );

        return $this;
    }

    /**
     * @throws SqlException
     */
    /**
     * @throws SqlException
     */
    private function buildSubqueryComparison(
        SelectStatementInterface $subquery,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator,
        ConditionConjunction $conjunction,
    ): SubqueryComparisonCondition {
        if (\is_string($operator)) {
            $operator = ConditionOperator::fromInput($operator);
        }

        if (
            $operator === ConditionOperator::IN ||
            $operator === ConditionOperator::NOT_IN ||
            $operator === ConditionOperator::IS_NULL ||
            $operator === ConditionOperator::IS_NOT_NULL
        ) {
            throw SqlException::fromUnsupportedSubqueryComparisonOperator(
                operator: $operator,
            );
        }

        if (!$subquery instanceof AbstractStatement) {
            // @codeCoverageIgnoreStart
            throw SqlException::fromSubqueryStatementMustExtendAbstractStatement(
                actualType: \get_debug_type($subquery),
            );
            // @codeCoverageIgnoreEnd
        }

        $parameterKey = 'where_' . $this->nextParameterOffset();

        $this->parameters[$parameterKey] = $value;

        return new SubqueryComparisonCondition(
            conjunction: $conjunction,
            subquery: $subquery,
            operator: $operator,
            parameter: ':' . $parameterKey,
        );
    }

    private function buildExists(
        SelectStatementInterface $subquery,
        ConditionConjunction $conjunction,
        bool $negated,
    ): ExistsCondition {
        if (!$subquery instanceof AbstractStatement) {
            // @codeCoverageIgnoreStart
            throw SqlException::fromSubqueryStatementMustExtendAbstractStatement(
                actualType: \get_debug_type($subquery),
            );
            // @codeCoverageIgnoreEnd
        }

        return new ExistsCondition(
            conjunction: $conjunction,
            negated: $negated,
            subquery: $subquery,
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    public function whereGroup(
        \Closure $callback,
    ): static {
        return $this->buildGroup(
            callback: $callback,
            conjunction: ConditionConjunction::AND,
            negated: false,
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    public function orWhereGroup(
        \Closure $callback,
    ): static {
        return $this->buildGroup(
            callback: $callback,
            conjunction: ConditionConjunction::OR,
            negated: false,
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    public function whereNot(
        \Closure $callback,
    ): static {
        return $this->buildGroup(
            callback: $callback,
            conjunction: ConditionConjunction::AND,
            negated: true,
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    public function orWhereNot(
        \Closure $callback,
    ): static {
        return $this->buildGroup(
            callback: $callback,
            conjunction: ConditionConjunction::OR,
            negated: true,
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    private function buildGroup(
        \Closure $callback,
        ConditionConjunction $conjunction,
        bool $negated,
    ): static {
        $before = \sizeof($this->conditions);

        $callback($this);

        if (\sizeof($this->conditions) === $before) {
            return $this;
        }

        $added = \array_slice($this->conditions, $before);
        $this->conditions = \array_slice($this->conditions, 0, $before);
        $this->conditions[] = new GroupCondition(
            conjunction: $conjunction,
            negated: $negated,
            conditions: $added,
        );

        return $this;
    }

    /**
     * @param list<string> $columns
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    private function addFullTextCondition(
        array $columns,
        string $search,
        ConditionConjunction $conjunction,
    ): static {
        if ($columns === []) {
            throw SqlException::fromFullTextWithoutColumns(); // @codeCoverageIgnore
        }

        $this->verifyFullTextIndex($columns);

        $this->conditions[] = new FullTextCondition(
            conjunction: $conjunction,
            columns: $columns,
            terms: FullText::terms($search),
        );

        return $this;
    }

    /**
     * @param non-empty-list<string> $columns
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    private function verifyFullTextIndex(
        array $columns,
    ): void {
        $connection = $this->connection;

        if ($connection === null) {
            throw DatabaseException::fromNoConnectionAvailable();
        }

        $connection->dialect->fullTextCondition(
            columns: $columns,
            terms: [],
        );

        $table = null;
        $names = [];

        foreach ($columns as $column) {
            $separator = \strrpos($column, '.');
            $columnTable = $separator === false
                ? $this->table
                : \substr($column, 0, $separator);

            if ($table !== null && $table !== $columnTable) {
                throw SqlException::fromFullTextColumnsSpanTables(
                    columns: $columns,
                );
            }

            $table = $columnTable;
            $names[] = $separator === false
                ? $column
                : \substr($column, $separator + 1);
        }

        /** @var string $table */
        $index = FullText::indexName(
            table: $table,
            columns: $names,
        );

        self::$verifiedFullTextIndexes ??= new \WeakMap();
        $verified = self::$verifiedFullTextIndexes[$connection] ?? [];

        if (isset($verified[$table . '.' . $index])) {
            return;
        }

        $prepared = $connection->dialect->indexExists(
            table: $table,
            name: $index,
        );

        $result = $connection->query(
            sql: $prepared->sql,
            parameters: $prepared->parameters,
        );

        if (!$connection->dialect->interpretBoolean($result->fetchRow()[0])) {
            throw SqlException::fromFullTextIndexMissing(
                table: $table,
                index: $index,
            );
        }

        $verified[$table . '.' . $index] = true;
        self::$verifiedFullTextIndexes[$connection] = $verified;
    }
}
