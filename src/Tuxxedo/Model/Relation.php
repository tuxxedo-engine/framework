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
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;

// @todo Layer a generic Paginator on top of page() — page() is the raw SQL-layer primitive; Paginator would compose it with totalCount to expose typed page-aware iteration
// @todo Chain methods on a builder-less prefetched Relation store the criteria stack but cannot apply it (no builder to refetch from). The empty-prefetched case Hydrator uses today is unaffected. A hybrid Relation (prefetched + builder) drops the prefetched on chain and refetches via the builder, courtesy of Part E. Revisit the builder-less case if filtering prefetched-only results becomes a real need — would need an in-memory predicate evaluator mirroring WhereStatementInterface semantics.
/**
 * @template TModel of object
 *
 * @implements RelationInterface<TModel>
 */
class Relation implements RelationInterface
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

    public int $count {
        get {
            return \count($this->materialize());
        }
    }

    /**
     * @var array<int, TModel>
     */
    public private(set) array $pendingAdds = [];

    /**
     * @var array<int, TModel>
     */
    public private(set) array $pendingRemoves = [];

    /**
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>, ?int, ?int): iterable<int, TModel>)|null $loaderBuilder
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): int)|null $countBuilder
     * @param array<int, TModel>|null $prefetched
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     */
    final private function __construct(
        private readonly ?\Closure $loaderBuilder = null,
        private readonly ?\Closure $countBuilder = null,
        private readonly ?array $prefetched = null,
        public readonly array $criteriaStack = [],
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
    ) {
    }

    /**
     * @template TItem of object
     *
     * @param \Closure(list<\Closure(WhereStatementInterface): void>, ?int, ?int): iterable<int, TItem> $loaderBuilder
     * @param \Closure(list<\Closure(WhereStatementInterface): void>): int $countBuilder
     * @return self<TItem>
     */
    public static function createFromBuilder(
        \Closure $loaderBuilder,
        \Closure $countBuilder,
    ): self {
        return new self(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
        );
    }

    /**
     * @template TItem of object
     *
     * @param array<int, TItem> $values
     * @return self<TItem>
     */
    public static function createFromPrefetched(
        array $values,
    ): self {
        return new self(
            prefetched: $values,
        );
    }

    /**
     * @template TItem of object
     *
     * @param array<int, TItem> $prefetched
     * @param \Closure(list<\Closure(WhereStatementInterface): void>, ?int, ?int): iterable<int, TItem> $loaderBuilder
     * @param \Closure(list<\Closure(WhereStatementInterface): void>): int $countBuilder
     * @param list<\Closure(WhereStatementInterface): void> $initialCriteriaStack
     * @return self<TItem>
     */
    public static function createFromPrefetchedWithBuilder(
        array $prefetched,
        \Closure $loaderBuilder,
        \Closure $countBuilder,
        array $initialCriteriaStack = [],
        ?int $initialLimit = null,
        ?int $initialOffset = null,
    ): self {
        return new self(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            prefetched: $prefetched,
            criteriaStack: $initialCriteriaStack,
            limit: $initialLimit,
            offset: $initialOffset,
        );
    }

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
     * @param TModel $item
     */
    public function add(
        object $item,
    ): void {
        foreach ($this->pendingRemoves as $index => $existing) {
            if ($existing === $item) {
                unset($this->pendingRemoves[$index]);

                return;
            }
        }

        $this->pendingAdds[] = $item;
    }

    /**
     * @param TModel $item
     */
    public function remove(
        object $item,
    ): void {
        foreach ($this->pendingAdds as $index => $existing) {
            if ($existing === $item) {
                unset($this->pendingAdds[$index]);

                return;
            }
        }

        $this->pendingRemoves[] = $item;
    }

    public function clearPending(): void
    {
        $this->pendingAdds = [];
        $this->pendingRemoves = [];
    }

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function where(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $value, $operator): void {
                $statement->where($column, $value, $operator);
            },
        );
    }

    /**
     * @param string|int|float|bool|null|non-empty-array<string|int|float|bool|null> $value
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function orWhere(
        string $column,
        string|int|float|bool|null|array $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $value, $operator): void {
                $statement->orWhere($column, $value, $operator);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function whereNull(
        string $column,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->whereNull($column);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function whereNotNull(
        string $column,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->whereNotNull($column);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function orWhereNull(
        string $column,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->orWhereNull($column);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function orWhereNotNull(
        string $column,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column): void {
                $statement->orWhereNotNull($column);
            },
        );
    }

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function whereIn(
        string $column,
        array $values,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->whereIn($column, $values);
            },
        );
    }

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function whereNotIn(
        string $column,
        array $values,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $values): void {
                $statement->whereNotIn($column, $values);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function whereBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->whereBetween($column, $from, $to);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function whereNotBetween(
        string $column,
        string|int|float|bool $from,
        string|int|float|bool $to,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($column, $from, $to): void {
                $statement->whereNotBetween($column, $from, $to);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function innerJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator): void {
                $statement->innerJoin($table, $first, $second, $operator);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function leftJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator): void {
                $statement->leftJoin($table, $first, $second, $operator);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function rightJoin(
        string $table,
        string $first,
        string $second,
        JoinOperator|string $operator = JoinOperator::EQUALS,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table, $first, $second, $operator): void {
                $statement->rightJoin($table, $first, $second, $operator);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function crossJoin(
        string $table,
    ): self {
        return $this->extend(
            criterion: static function (WhereStatementInterface $statement) use ($table): void {
                $statement->crossJoin($table);
            },
        );
    }

    /**
     * @return self<TModel>
     */
    #[\NoDiscard]
    public function page(
        int $limit,
        ?int $offset = null,
    ): self {
        return new self(
            loaderBuilder: $this->loaderBuilder,
            countBuilder: $this->countBuilder,
            prefetched: $this->loaderBuilder !== null
                ? null
                : $this->prefetched,
            criteriaStack: $this->criteriaStack,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param \Closure(WhereStatementInterface): void $criterion
     * @return self<TModel>
     */
    private function extend(
        \Closure $criterion,
    ): self {
        $stack = $this->criteriaStack;
        $stack[] = $criterion;

        return new self(
            loaderBuilder: $this->loaderBuilder,
            countBuilder: $this->countBuilder,
            prefetched: $this->loaderBuilder !== null
                ? null
                : $this->prefetched,
            criteriaStack: $stack,
            limit: $this->limit,
            offset: $this->offset,
        );
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
        if (
            $this->pendingAdds !== [] ||
            $this->pendingRemoves !== [] ||
            $this->cache !== null ||
            $this->prefetched !== null ||
            $this->loaderBuilder === null
        ) {
            foreach ($this->materialize() as $item) {
                return $item;
            }

            return null;
        }

        $loaded = ($this->loaderBuilder)($this->criteriaStack, 1, $this->offset);

        foreach ($loaded as $item) {
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
        return $this->prefetched !== null || $this->cache !== null;
    }

    private function computeTotalCount(): int
    {
        if ($this->prefetched !== null) {
            return \count($this->prefetched);
        }

        if ($this->countBuilder !== null) {
            return ($this->countBuilder)($this->criteriaStack);
        }

        return \count($this->materialize());
    }

    /**
     * @return array<int, TModel>
     */
    private function materialize(): array
    {
        $base = $this->loadBase();

        if ($this->pendingAdds === [] && $this->pendingRemoves === []) {
            return $base;
        }

        $overlay = $base;

        foreach ($this->pendingRemoves as $itemToRemove) {
            foreach ($overlay as $index => $existing) {
                if ($existing === $itemToRemove) {
                    unset($overlay[$index]);
                }
            }
        }

        $overlay = \array_values($overlay);

        foreach ($this->pendingAdds as $itemToAdd) {
            $overlay[] = $itemToAdd;
        }

        return $overlay;
    }

    /**
     * @return array<int, TModel>
     */
    private function loadBase(): array
    {
        if ($this->prefetched !== null) {
            return $this->prefetched;
        }

        if ($this->cache !== null) {
            return $this->cache;
        }

        if ($this->loaderBuilder === null) {
            return [];
        }

        $loaded = ($this->loaderBuilder)($this->criteriaStack, $this->limit, $this->offset);

        return $this->cache = \is_array($loaded)
            ? $loaded
            : \iterator_to_array($loaded);
    }
}
