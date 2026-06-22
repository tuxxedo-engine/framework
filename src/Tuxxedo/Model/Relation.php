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

use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;

/**
 * @template TModel of object
 *
 * @extends AbstractQueryable<TModel>
 * @implements RelationInterface<TModel>
 */
class Relation extends AbstractQueryable implements RelationInterface
{
    /**
     * @var array<int, TModel>
     */
    public private(set) array $pendingAdds = [];

    /**
     * @var array<int, TModel>
     */
    public private(set) array $pendingRemoves = [];

    /**
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string, direction: OrderDirection}>, ?int, ?int): iterable<int, TModel>)|null $loaderBuilder
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): int)|null $countBuilder
     * @param array<int, TModel>|null $prefetched
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string, direction: OrderDirection}> $orderBy
     */
    final private function __construct(
        ?\Closure $loaderBuilder = null,
        ?\Closure $countBuilder = null,
        private readonly ?array $prefetched = null,
        array $criteriaStack = [],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null,
    ) {
        parent::__construct(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            criteriaStack: $criteriaStack,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @template TItem of object
     *
     * @param \Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string, direction: OrderDirection}>, ?int, ?int): iterable<int, TItem> $loaderBuilder
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
     * @param \Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string, direction: OrderDirection}>, ?int, ?int): iterable<int, TItem> $loaderBuilder
     * @param \Closure(list<\Closure(WhereStatementInterface): void>): int $countBuilder
     * @param list<\Closure(WhereStatementInterface): void> $initialCriteriaStack
     * @param list<array{column: string, direction: OrderDirection}> $initialOrderBy
     * @return self<TItem>
     */
    public static function createFromPrefetchedWithBuilder(
        array $prefetched,
        \Closure $loaderBuilder,
        \Closure $countBuilder,
        array $initialCriteriaStack = [],
        array $initialOrderBy = [],
        ?int $initialLimit = null,
        ?int $initialOffset = null,
    ): self {
        return new self(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            prefetched: $prefetched,
            criteriaStack: $initialCriteriaStack,
            orderBy: $initialOrderBy,
            limit: $initialLimit,
            offset: $initialOffset,
        );
    }

    /**
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string, direction: OrderDirection}> $orderBy
     * @return static
     */
    protected function cloneWith(
        array $criteriaStack,
        array $orderBy,
        ?int $limit,
        ?int $offset,
    ): static {
        return new static(
            loaderBuilder: $this->loaderBuilder,
            countBuilder: $this->countBuilder,
            prefetched: $this->loaderBuilder !== null
                ? null
                : $this->prefetched,
            criteriaStack: $criteriaStack,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
        );
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
     * @return TModel|null
     */
    #[\NoDiscard]
    public function first(): ?object
    {
        if (
            $this->pendingAdds !== [] ||
            $this->pendingRemoves !== [] ||
            $this->prefetched !== null
        ) {
            foreach ($this->materialize() as $item) {
                return $item;
            }

            return null;
        }

        return parent::first();
    }

    public function isMaterialized(): bool
    {
        return parent::isMaterialized() || $this->prefetched !== null;
    }

    protected function computeTotalCount(): int
    {
        if ($this->prefetched !== null) {
            return \count($this->prefetched);
        }

        return parent::computeTotalCount();
    }

    /**
     * @return array<int, TModel>
     */
    protected function materialize(): array
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
    protected function loadBase(): array
    {
        if ($this->prefetched !== null) {
            return $this->prefetched;
        }

        return parent::loadBase();
    }
}
