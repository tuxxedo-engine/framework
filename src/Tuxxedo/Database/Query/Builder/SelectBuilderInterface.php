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

namespace Tuxxedo\Database\Query\Builder;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\HydratableInterface;
use Tuxxedo\Database\SqlException;

interface SelectBuilderInterface extends WhereBuilderInterface
{
    public function select(
        string ...$columns,
    ): static;

    public function distinct(): static;

    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static;

    public function groupBy(
        string ...$columns,
    ): static;

    public function having(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    public function orHaving(
        string $column,
        string|int|float|bool|null $value,
        ConditionOperator|string $operator = ConditionOperator::EQUALS,
    ): static;

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function havingIn(
        string $column,
        array $values,
    ): static;

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function havingNotIn(
        string $column,
        array $values,
    ): static;

    public function havingNull(
        string $column,
    ): static;

    public function havingNotNull(
        string $column,
    ): static;

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function orHavingIn(
        string $column,
        array $values,
    ): static;

    /**
     * @param non-empty-array<string|int|float|bool|null> $values
     */
    public function orHavingNotIn(
        string $column,
        array $values,
    ): static;

    public function orHavingNull(
        string $column,
    ): static;

    public function orHavingNotNull(
        string $column,
    ): static;

    public function limit(
        int $limit,
        ?int $offset = null,
    ): static;

    /**
     * @template TClassName of object&HydratableInterface
     *
     * @param class-string<TClassName>|\Closure(mixed[] $properties): TClassName $class
     * @return TClassName|null
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    public function fetch(
        string|\Closure $class,
    ): ?object;

    /**
     * @template TClassName of object&HydratableInterface
     *
     * @param class-string<TClassName>|\Closure(mixed[] $properties): TClassName $class
     * @return \Generator<TClassName>
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    public function fetchAll(
        string|\Closure $class,
    ): \Generator;
}
