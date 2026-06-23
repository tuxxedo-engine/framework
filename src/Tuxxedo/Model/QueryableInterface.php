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

namespace Tuxxedo\Model;

use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Join\JoinOperator;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Pagination\PagedInterface;

// @todo whereHas / whereDoesntHave - model-aware chain method that filters parents by relation existence, e.g. $userQuery->whereHas('posts', fn ($q) => $q->where('published', true)) → WHERE EXISTS (SELECT 1 FROM posts WHERE posts.user_id = users.id AND posts.published = ?). Blocked on the whereExists + whereColumn primitives in WhereStatementInterface. Implementation also needs access to the manager's metadata to resolve relation property names to their FK columns
// @todo withCount - adds a correlated COUNT subquery as a synthetic column on each fetched row: ->withCount('posts') populates a $postsCount property. Needs a "subquery in SELECT list" mechanism on SelectStatement (separate from WHERE-side subqueries) plus relation metadata lookup
/**
 * @template TModel of object
 *
 * @extends PagedInterface<TModel>
 * @extends \IteratorAggregate<int, TModel>
 * @extends \ArrayAccess<int, TModel>
 */
interface QueryableInterface extends PagedInterface, \IteratorAggregate, \Countable, \ArrayAccess
{
    public int $totalCount {
        get;
    }

    /**
     * @var int<0, max>
     */
    public int $count {
        get;
    }

    /**
     * @return \Generator<int, TModel>
     */
    public function getIterator(): \Generator;

    #[\NoDiscard]
    public function isMaterialized(): bool;

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    #[\NoDiscard]
    public function where(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    #[\NoDiscard]
    public function orWhere(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    #[\NoDiscard]
    public function whereNull(
        string $column,
    ): static;

    #[\NoDiscard]
    public function whereNotNull(
        string $column,
    ): static;

    #[\NoDiscard]
    public function orWhereNull(
        string $column,
    ): static;

    #[\NoDiscard]
    public function orWhereNotNull(
        string $column,
    ): static;

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function whereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static;

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function whereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static;

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function orWhereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static;

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function orWhereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static;

    #[\NoDiscard]
    public function whereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    #[\NoDiscard]
    public function whereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    #[\NoDiscard]
    public function orWhereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    #[\NoDiscard]
    public function orWhereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static;

    #[\NoDiscard]
    public function whereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    #[\NoDiscard]
    public function orWhereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    /**
     * @param array<string, string|int|float|bool|null> $bindings
     */
    #[\NoDiscard]
    public function whereRaw(
        string $sql,
        array $bindings = [],
    ): static;

    #[\NoDiscard]
    public function whereLike(
        string $column,
        string $pattern,
    ): static;

    #[\NoDiscard]
    public function whereNotLike(
        string $column,
        string $pattern,
    ): static;

    #[\NoDiscard]
    public function orWhereLike(
        string $column,
        string $pattern,
    ): static;

    #[\NoDiscard]
    public function orWhereNotLike(
        string $column,
        string $pattern,
    ): static;

    #[\NoDiscard]
    public function innerJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    #[\NoDiscard]
    public function leftJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    #[\NoDiscard]
    public function rightJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static;

    #[\NoDiscard]
    public function crossJoin(
        string $table,
    ): static;

    #[\NoDiscard]
    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static;

    #[\NoDiscard]
    public function page(
        int $limit,
        ?int $offset = null,
    ): static;

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     * @return static
     */
    #[\NoDiscard]
    public function whereGroup(
        \Closure $callback,
    ): static;

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     * @return static
     */
    #[\NoDiscard]
    public function orWhereGroup(
        \Closure $callback,
    ): static;

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     * @return static
     */
    #[\NoDiscard]
    public function whereNot(
        \Closure $callback,
    ): static;

    /**
     * @param \Closure(WhereStatementInterface): void $callback
     * @return static
     */
    #[\NoDiscard]
    public function orWhereNot(
        \Closure $callback,
    ): static;

    /**
     * @return TModel|null
     */
    #[\NoDiscard]
    public function first(): ?object;

    /**
     * @return \Generator<int, TModel>
     */
    #[\NoDiscard]
    public function fetchAll(): \Generator;
}
