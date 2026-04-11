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
use Tuxxedo\Database\Query\OrderDirection;
use Tuxxedo\Database\SqlException;

interface SelectBuilderInterface extends WhereBuilderInterface
{
    public function select(
        string ...$columns,
    ): static;

    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static;

    public function groupBy(
        string ...$columns,
    ): static;

    public function limit(
        int $limit,
    ): static;

    public function offset(
        int $offset,
    ): static;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return TClassName|null
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    public function fetch(
        string|\Closure $class,
    ): ?object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return \Generator<TClassName>
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    public function fetchAll(
        string|\Closure $class,
    ): \Generator;
}
