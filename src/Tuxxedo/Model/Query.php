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

use Tuxxedo\Database\Query\Statement\WhereStatementInterface;

/**
 * @template TModel of object
 *
 * @extends AbstractQueryable<TModel>
 */
// @todo Support eager loading via a chain method (e.g., ->with(['posts.comments'])) so Query can express what findAll's $with parameter does today
class Query extends AbstractQueryable
{
    /**
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>, ?int, ?int): iterable<int, TModel>)|null $loaderBuilder
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): int)|null $countBuilder
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     */
    final private function __construct(
        ?\Closure $loaderBuilder = null,
        ?\Closure $countBuilder = null,
        array $criteriaStack = [],
        ?int $limit = null,
        ?int $offset = null,
    ) {
        parent::__construct(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            criteriaStack: $criteriaStack,
            limit: $limit,
            offset: $offset,
        );
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
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @return static
     */
    protected function cloneWith(
        array $criteriaStack,
        ?int $limit,
        ?int $offset,
    ): static {
        return new static(
            loaderBuilder: $this->loaderBuilder,
            countBuilder: $this->countBuilder,
            criteriaStack: $criteriaStack,
            limit: $limit,
            offset: $offset,
        );
    }
}
