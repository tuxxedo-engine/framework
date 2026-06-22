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
use Tuxxedo\Database\Hydrator\HydratableInterface;
use Tuxxedo\Database\Hydrator\HydratorInterface;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\SqlException;

// @todo havingBetween / havingNotBetween / orHavingBetween / orHavingNotBetween — HAVING parity with WHERE's between methods; the rest of the HAVING surface already mirrors WHERE
interface SelectStatementInterface extends WhereStatementInterface
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
     * @template TClassName of object
     *
     * @param class-string<TClassName>|class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return TClassName|null
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    #[\NoDiscard]
    public function fetch(
        string|\Closure $class,
        ?HydratorInterface $hydrator = null,
        ?ConnectionInterface $connection = null,
    ): ?object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return \Generator<int, TClassName>
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    #[\NoDiscard]
    public function fetchAll(
        string|\Closure $class,
        ?HydratorInterface $hydrator = null,
        ?ConnectionInterface $connection = null,
    ): \Generator;
}
