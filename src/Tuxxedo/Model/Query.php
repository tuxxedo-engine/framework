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
 */
class Query extends AbstractQueryable
{
    /**
     * @var array<string, ?\Closure(Relation<object>): Relation<object>>|null
     */
    private readonly ?array $with;

    /**
     * @var (\Closure(list<object>, array<string, ?\Closure(Relation<object>): Relation<object>>): void)|null
     */
    private readonly ?\Closure $eagerLoader;

    /**
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string, direction: OrderDirection}>, ?int, ?int): iterable<int, TModel>)|null $loaderBuilder
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): int)|null $countBuilder
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string, direction: OrderDirection}> $orderBy
     * @param array<string, ?\Closure(Relation<object>): Relation<object>>|null $with
     * @param (\Closure(list<object>, array<string, ?\Closure(Relation<object>): Relation<object>>): void)|null $eagerLoader
     * @param ?class-string<TModel> $modelClass
     */
    final private function __construct(
        ?\Closure $loaderBuilder = null,
        ?\Closure $countBuilder = null,
        array $criteriaStack = [],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null,
        ?array $with = null,
        ?\Closure $eagerLoader = null,
        ?ModelsManagerInterface $manager = null,
        ?string $modelClass = null,
    ) {
        parent::__construct(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            criteriaStack: $criteriaStack,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
            manager: $manager,
            modelClass: $modelClass,
        );

        $this->with = $with;
        $this->eagerLoader = $eagerLoader;
    }

    /**
     * @template TItem of object
     *
     * @param \Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string, direction: OrderDirection}>, ?int, ?int): iterable<int, TItem> $loaderBuilder
     * @param \Closure(list<\Closure(WhereStatementInterface): void>): int $countBuilder
     * @param (\Closure(list<object>, array<string, ?\Closure(Relation<object>): Relation<object>>): void)|null $eagerLoader
     * @param ?class-string<TItem> $modelClass
     * @return self<TItem>
     */
    public static function createFromBuilder(
        \Closure $loaderBuilder,
        \Closure $countBuilder,
        ?\Closure $eagerLoader = null,
        ?ModelsManagerInterface $manager = null,
        ?string $modelClass = null,
    ): self {
        return new self(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            eagerLoader: $eagerLoader,
            manager: $manager,
            modelClass: $modelClass,
        );
    }

    /**
     * @param array<string, ?\Closure(Relation<object>): Relation<object>> $with
     */
    #[\NoDiscard]
    public function with(
        array $with,
    ): static {
        return new static(
            loaderBuilder: $this->loaderBuilder,
            countBuilder: $this->countBuilder,
            criteriaStack: $this->criteriaStack,
            orderBy: $this->orderBy,
            limit: $this->limit,
            offset: $this->offset,
            with: $this->with === null
                ? $with
                : \array_merge($this->with, $with),
            eagerLoader: $this->eagerLoader,
            manager: $this->manager,
            modelClass: $this->modelClass,
        );
    }

    /**
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string, direction: OrderDirection}> $orderBy
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
            criteriaStack: $criteriaStack,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
            with: $this->with,
            eagerLoader: $this->eagerLoader,
            manager: $this->manager,
            modelClass: $this->modelClass,
        );
    }

    /**
     * @return TModel|null
     */
    #[\NoDiscard]
    public function first(): ?object
    {
        $result = parent::first();

        if (
            $result !== null &&
            $this->with !== null &&
            \sizeof($this->with) > 0 &&
            $this->eagerLoader !== null
        ) {
            ($this->eagerLoader)(
                [
                    $result,
                ],
                $this->with,
            );
        }

        return $result;
    }

    /**
     * @return array<int, TModel>
     */
    protected function loadBase(): array
    {
        $wasCached = $this->isMaterialized();
        $rows = parent::loadBase();

        if (
            !$wasCached &&
            \sizeof($rows) > 0 &&
            $this->with !== null &&
            \sizeof($this->with) > 0 &&
            $this->eagerLoader !== null
        ) {
            ($this->eagerLoader)(\array_values($rows), $this->with);
        }

        return $rows;
    }
}
