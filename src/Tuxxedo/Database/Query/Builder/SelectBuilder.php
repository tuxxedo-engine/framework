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

class SelectBuilder extends AbstractWhereBuilder implements SelectBuilderInterface
{
    /**
     * @var string[]
     */
    private array $columns = [];

    /**
     * @var array<string, OrderDirection>
     */
    private array $orderBy = [];

    /**
     * @var string[]
     */
    private array $groupBy = [];

    private ?int $limit = null;
    private ?int $offset = null;

    protected function generateSql(): string
    {
        $columns = \sizeof($this->columns) > 0
            ? \join(', ', $this->columns)
            : '*';

        $sql = \sprintf(
            'SELECT %s FROM %s%s',
            $columns,
            $this->connection->dialect->identifier($this->table),
            $this->generateWhereSql(),
        );

        if (\sizeof($this->groupBy) > 0) {
            $sql .= \sprintf(
                ' GROUP BY %s',
                \join(', ', $this->groupBy),
            );
        }

        if (\sizeof($this->orderBy) > 0) {
            $clauses = \array_map(
                static fn (string $identifier, OrderDirection $direction): string => $identifier . ' ' . $direction->name,
                \array_keys($this->orderBy),
                \array_values($this->orderBy),
            );

            $sql .= \sprintf(
                ' ORDER BY %s',
                \join(', ', $clauses),
            );
        }

        if ($this->limit !== null) {
            $sql .= \sprintf(
                ' LIMIT %d',
                $this->limit,
            );

            if ($this->offset !== null) {
                $sql .= \sprintf(
                    ' OFFSET %d',
                    $this->offset,
                );
            }
        }

        return $sql;
    }

    public function select(
        string ...$columns,
    ): static {
        foreach ($columns as $column) {
            $this->columns[] = $column === '*' || (\str_contains($column, '(') && \str_contains($column, ')'))
                ? $column
                : $this->connection->dialect->qualifiedIdentifier($column);
        }

        return $this;
    }

    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static {
        if (\is_string($direction)) {
            $direction = OrderDirection::from($direction);
        }

        $this->orderBy[$this->connection->dialect->identifier($column)] = $direction;

        return $this;
    }

    public function groupBy(
        string ...$columns,
    ): static {
        foreach ($columns as $column) {
            $this->groupBy[] = $this->connection->dialect->identifier($column);
        }

        return $this;
    }

    public function limit(
        int $limit,
        ?int $offset = null,
    ): static {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

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
    ): ?object {
        $result = $this->execute();

        if ($result->count() > 0) {
            return $result->fetchObject($class);
        }

        return null;
    }

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
    ): \Generator {
        yield from $this->execute()->fetchAll($class);
    }
}
