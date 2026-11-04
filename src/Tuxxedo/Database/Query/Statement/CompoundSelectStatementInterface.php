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
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Hydrator\HydratableInterface;
use Tuxxedo\Database\Hydrator\HydratorInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\SqlException;

interface CompoundSelectStatementInterface extends SubqueryRenderableInterface
{
    public ?ConnectionInterface $connection {
        get;
    }

    public function union(
        SubqueryRenderableInterface $member,
        SubqueryRenderableInterface ...$members,
    ): static;

    public function unionAll(
        SubqueryRenderableInterface $member,
        SubqueryRenderableInterface ...$members,
    ): static;

    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static;

    public function limit(
        int $limit,
        ?int $offset = null,
    ): static;

    /**
     * @throws DatabaseException
     * @throws SqlException
     */
    public function compile(
        ?ConnectionInterface $connection = null,
    ): StatementParserResultInterface;

    /**
     * @throws DatabaseException
     * @throws SqlException
     */
    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface;

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
