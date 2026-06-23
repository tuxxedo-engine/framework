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

/**
 * @template TModel of object
 *
 * @implements QueryableInterface<TModel>
 */
abstract class AbstractQueryable implements QueryableInterface
{
    /**
     * @var array<int, TModel>|null
     */
    private ?array $cache = null;

    private ?int $cachedTotalCount = null;

    public int $totalCount {
        get {
            return $this->cachedTotalCount ??= $this->computeTotalCount();
        }
    }

    /**
     * @var int<0, max>
     */
    public int $count {
        get {
            return \count($this->materialize());
        }
    }

    /**
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string, direction: OrderDirection}>, ?int, ?int): iterable<int, TModel>)|null $loaderBuilder
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): int)|null $countBuilder
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string, direction: OrderDirection}> $orderBy
     */
    protected function __construct(
        protected readonly ?\Closure $loaderBuilder = null,
        protected readonly ?\Closure $countBuilder = null,
        public readonly array $criteriaStack = [],
        public readonly array $orderBy = [],
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
    ) {
    }

    /**
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string, direction: OrderDirection}> $orderBy
     */
    abstract protected function cloneWith(
        array $criteriaStack,
        array $orderBy,
        ?int $limit,
        ?int $offset,
    ): static;

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->materialize()[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->materialize()[$offset];
    }

    /**
     * @return never
     *
     * @throws ModelException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw ModelException::fromImmutableRelation();
    }

    /**
     * @return never
     *
     * @throws ModelException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw ModelException::fromImmutableRelation();
    }

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    #[\NoDiscard]
    public function where(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $value, $operator): void {
                $statement->where($column, $value, $operator);
            },
        );
    }

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     */
    #[\NoDiscard]
    public function orWhere(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $value, $operator): void {
                $statement->orWhere($column, $value, $operator);
            },
        );
    }

    #[\NoDiscard]
    public function whereNull(
        string $column,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->whereNull($column);
            },
        );
    }

    #[\NoDiscard]
    public function whereNotNull(
        string $column,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->whereNotNull($column);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNull(
        string $column,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->orWhereNull($column);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNotNull(
        string $column,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->orWhereNotNull($column);
            },
        );
    }

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function whereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->whereIn($column, $values);
            },
        );
    }

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function whereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->whereNotIn($column, $values);
            },
        );
    }

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function orWhereIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->orWhereIn($column, $values);
            },
        );
    }

    /**
     * @param SelectStatementInterface|non-empty-array<string|int|float|bool|null> $values
     */
    #[\NoDiscard]
    public function orWhereNotIn(
        string $column,
        SelectStatementInterface|array $values,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->orWhereNotIn($column, $values);
            },
        );
    }

    #[\NoDiscard]
    public function whereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->whereBetween($column, $from, $to);
            },
        );
    }

    #[\NoDiscard]
    public function whereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->whereNotBetween($column, $from, $to);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->orWhereBetween($column, $from, $to);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->orWhereNotBetween($column, $from, $to);
            },
        );
    }

    #[\NoDiscard]
    public function whereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $other, $operator): void {
                $statement->whereColumn($column, $other, $operator);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereColumn(
        string $column,
        string $other,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $other, $operator): void {
                $statement->orWhereColumn($column, $other, $operator);
            },
        );
    }

    /**
     * @param array<string, string|int|float|bool|null> $bindings
     */
    #[\NoDiscard]
    public function whereRaw(
        string $sql,
        array $bindings = [],
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($sql, $bindings): void {
                $statement->whereRaw($sql, $bindings);
            },
        );
    }

    #[\NoDiscard]
    public function whereLike(
        string $column,
        string $pattern,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $pattern): void {
                $statement->whereLike($column, $pattern);
            },
        );
    }

    #[\NoDiscard]
    public function whereNotLike(
        string $column,
        string $pattern,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $pattern): void {
                $statement->whereNotLike($column, $pattern);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereLike(
        string $column,
        string $pattern,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $pattern): void {
                $statement->orWhereLike($column, $pattern);
            },
        );
    }

    #[\NoDiscard]
    public function orWhereNotLike(
        string $column,
        string $pattern,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $pattern): void {
                $statement->orWhereNotLike($column, $pattern);
            },
        );
    }

    #[\NoDiscard]
    public function innerJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator): void {
                $statement->innerJoin($table, $first, $second, $operator);
            },
        );
    }

    #[\NoDiscard]
    public function leftJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator): void {
                $statement->leftJoin($table, $first, $second, $operator);
            },
        );
    }

    #[\NoDiscard]
    public function rightJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator): void {
                $statement->rightJoin($table, $first, $second, $operator);
            },
        );
    }

    #[\NoDiscard]
    public function crossJoin(
        string $table,
    ): static {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table): void {
                $statement->crossJoin($table);
            },
        );
    }

    #[\NoDiscard]
    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static {
        if (\is_string($direction)) {
            $direction = OrderDirection::from($direction);
        }

        $orderBy = $this->orderBy;
        $orderBy[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this->cloneWith(
            criteriaStack: $this->criteriaStack,
            orderBy: $orderBy,
            limit: $this->limit,
            offset: $this->offset,
        );
    }

    #[\NoDiscard]
    public function page(
        int $limit,
        ?int $offset = null,
    ): static {
        return $this->cloneWith(
            criteriaStack: $this->criteriaStack,
            orderBy: $this->orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @return iterable<int, TModel>
     */
    public function slice(
        int $limit,
        int $offset,
    ): iterable {
        return $this->page($limit, $offset);
    }

    /**
     * @return \Generator<int, TModel>
     */
    #[\NoDiscard]
    public function fetchAll(): \Generator
    {
        yield from $this->materialize();
    }

    /**
     * @return TModel|null
     */
    #[\NoDiscard]
    public function first(): ?object
    {
        if ($this->cache === null && $this->loaderBuilder !== null) {
            $loaded = ($this->loaderBuilder)($this->criteriaStack, $this->orderBy, 1, $this->offset);

            foreach ($loaded as $item) {
                return $item;
            }

            return null;
        }

        foreach ($this->materialize() as $item) {
            return $item;
        }

        return null;
    }

    public function getIterator(): \Generator
    {
        return $this->fetchAll();
    }

    public function count(): int
    {
        return $this->count;
    }

    public function isMaterialized(): bool
    {
        return $this->cache !== null;
    }

    protected function computeTotalCount(): int
    {
        if ($this->countBuilder !== null) {
            return ($this->countBuilder)($this->criteriaStack);
        }

        return \count($this->materialize());
    }

    /**
     * @return array<int, TModel>
     */
    protected function materialize(): array
    {
        return $this->loadBase();
    }

    /**
     * @return array<int, TModel>
     */
    protected function loadBase(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if ($this->loaderBuilder === null) {
            return [];
        }

        $loaded = ($this->loaderBuilder)($this->criteriaStack, $this->orderBy, $this->limit, $this->offset);

        return $this->cache = \is_array($loaded)
            ? $loaded
            : \iterator_to_array($loaded);
    }

    /**
     * @param \Closure(WhereStatementInterface): void $criterion
     */
    private function extend(
        \Closure $criterion,
    ): static {
        $stack = $this->criteriaStack;
        $stack[] = $criterion;

        return $this->cloneWith(
            criteriaStack: $stack,
            orderBy: $this->orderBy,
            limit: $this->limit,
            offset: $this->offset,
        );
    }
}
