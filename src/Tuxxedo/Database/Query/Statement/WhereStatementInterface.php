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

// @todo Consider multi binding for one condition, e.g. where('a = :a AND b > :b', ['a' => ..., 'b' => ...])
// @todo whereIn / whereNotIn / orWhereIn / orWhereNotIn subquery form — accept SelectBuilderInterface alongside the value array so callers can write `WHERE col IN (SELECT ... FROM ... WHERE ...)`. Would let Model's Through hydration use IN-dedupe semantics in a single round-trip without JOIN+DISTINCT, and lets callers compose subqueries without manually pre-running them
// @todo whereExists / whereNotExists / orWhereExists / orWhereNotExists — correlated-subquery existence checks. Common ORM-level need for "find parents where any child matches X"; today the only path is JOIN-and-DISTINCT or two queries
// @todo whereGroup with closure form — support nested AND/OR groupings, e.g. ->where('a', 1)->orWhereGroup(fn($q) => $q->where('b', 2)->where('c', 3)). Current flat where() chain can't express "a = 1 OR (b = 2 AND c = 3)"
// @todo whereColumn / orWhereColumn — compare two columns directly (WHERE a.x = b.y) instead of column-vs-value. Needed for self-joins, cross-table correlations without JOIN, and especially correlated subqueries when paired with whereExists. Hydrator's HasManyThrough could drop its JOIN+DISTINCT once whereColumn + whereExists exist
// @todo whereNot / orWhereNot (closure form) — negated grouping mirror of whereGroup, e.g. ->whereNot(fn($q) => $q->where('a', 1)->where('b', 2)) → WHERE NOT (a = ? AND b = ?). Shares the sub-builder mechanism whereGroup needs
// @todo Subquery-as-RHS for any comparison operator — broader form of the whereIn subquery TODO above: where('cnt', '>', SelectStatement) → WHERE cnt > (SELECT ...). Reuses the same subquery+parameter-merging infrastructure
interface WhereStatementInterface extends StatementInterface
{
    public function hasConstraints(): bool;
    public function hasConditionConstraints(): bool;
    public function hasJoinConstraints(): bool;

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function where(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    public function orWhere(
        string $column,
        string|int|float|bool|null|array $value,
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
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function whereIn(
        string $column,
        array $values,
    ): static;

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function whereNotIn(
        string $column,
        array $values,
    ): static;

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function orWhereIn(
        string $column,
        array $values,
    ): static;

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function orWhereNotIn(
        string $column,
        array $values,
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
}
