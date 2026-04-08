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

namespace Tuxxedo\Database\Driver\Pgsql;

use PgSql\Result;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractResultSet;
use Tuxxedo\Database\Driver\HydratableInterface;
use Tuxxedo\Database\Driver\ResultRowInterface;

class PgsqlResultSet extends AbstractResultSet
{
    private int $pointer = 0;
    private int $numRows;

    public function __construct(
        protected ContainerInterface $container,
        private Result $result,
        public readonly int $affectedRows = 0,
    ) {
        $this->numRows = \pg_num_rows($this->result);
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return TClassName
     */
    public function fetchObject(
        string|\Closure $class = ResultRowInterface::class,
    ): object {
        if ($this->numRows === 0) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_assoc($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch();
        }

        return parent::hydrate($class, $row);
    }

    public function fetchArray(): array
    {
        if ($this->numRows === 0) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_array($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch();
        }

        return $row;
    }

    public function fetchAssoc(): array
    {
        if ($this->numRows === 0) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_assoc($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch();
        }

        return $row;
    }

    public function fetchRow(): array
    {
        if ($this->numRows === 0) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_row($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch();
        }

        return \array_values($row);
    }

    public function count(): int
    {
        /** @var int<0, max> */
        return $this->numRows;
    }

    public function current(): ResultRowInterface
    {
        if ($this->numRows > 0) {
            \pg_result_seek($this->result, $this->pointer);
        }

        return $this->fetchObject();
    }

    public function key(): int
    {
        return $this->pointer;
    }

    public function next(): void
    {
        $this->pointer++;
    }

    public function rewind(): void
    {
        $this->pointer = 0;
    }

    public function valid(): bool
    {
        return $this->numRows > 0 && $this->pointer < $this->numRows;
    }
}
