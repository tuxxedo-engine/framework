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

use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Join\JoinOperator;

// @todo whereExists / whereNotExists / orWhereExists / orWhereNotExists — correlated-subquery existence checks. Common ORM-level need for "find parents where any child matches X"; today the only path is JOIN-and-DISTINCT or two queries
interface WhereStatementInterface extends StatementInterface
{
    public function hasConstraints(): bool;
    public function hasConditionConstraints(): bool;
    public function hasJoinConstraints(): bool;

    /**
     * @param SelectStatementInterface|string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function where(
        string $column,
        SelectStatementInterface|string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    /**
     * @param SelectStatementInterface|string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function orWhere(
        string $column,
        SelectStatementInterface|string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    public function orWhereNull(
        string $column,
    ): static;

    public function orWhereNotNull(
        string $column,
    ): static;

    public function whereNull(
        string $column,
    ): static;

    public function whereNotNull(
        string $column,
    ): static;

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    public function whereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static;

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    public function whereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static;

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    public function orWhereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static;

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    public function orWhereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static;

    public function innerJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    public function leftJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    public function rightJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    public function crossJoin(
        string $table,
    ): static;

    public function whereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    public function whereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    public function orWhereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    public function orWhereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    public function whereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    public function orWhereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    /**
     * @param array<string, string|int|float|bool|null> $bindings
     */
    public function whereRaw(
        string $sql,
        array $bindings = [],
    ): static;

    public function whereLike(
        string $column,
        string $pattern,
    ): static;

    public function whereNotLike(
        string $column,
        string $pattern,
    ): static;

    public function orWhereLike(
        string $column,
        string $pattern,
    ): static;

    public function orWhereNotLike(
        string $column,
        string $pattern,
    ): static;

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    public function whereGroup(
        \Closure $callback,
    ): static;

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    public function orWhereGroup(
        \Closure $callback,
    ): static;

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    public function whereNot(
        \Closure $callback,
    ): static;

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     */
    public function orWhereNot(
        \Closure $callback,
    ): static;
}
