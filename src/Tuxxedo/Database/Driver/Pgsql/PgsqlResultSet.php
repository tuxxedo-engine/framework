<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Database\Driver\Pgsql;

use PgSql\Result;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ResultRow;
use Tuxxedo\Database\Driver\ResultRowInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;

class PgsqlResultSet implements ResultSetInterface
{
    private int $pointer = 0;
    private int $numRows;

    public function __construct(
        private ?Result $result,
        public readonly int $affectedRows = 0,
    ) {
        if ($this->result !== null) {
            $this->numRows = \pg_num_rows($this->result);
        } else {
            $this->numRows = 0;
        }
    }

    public function fetchAllAsArray(): array
    {
        $rows = [];

        foreach ($this as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function fetchAllAsGenerator(): \Generator
    {
        foreach ($this as $row) {
            yield $row;
        }
    }

    public function fetch(): ResultRowInterface
    {
        return $this->fetchObject();
    }

    public function fetchObject(): ResultRowInterface
    {
        if ($this->result === null) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_assoc($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch();
        }

        return new ResultRow(
            properties: $row,
        );
    }

    public function fetchArray(): array
    {
        if ($this->result === null) {
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
        if ($this->result === null) {
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
        if ($this->result === null) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_row($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch();
        }

        return \array_values($row);
    }

    public function free(): void
    {
        if ($this->result !== null) {
            \pg_free_result($this->result);

            $this->result = null;
            $this->pointer = 0;
            $this->numRows = 0;
        }
    }

    public function count(): int
    {
        /** @var int<0, max> */
        return $this->numRows;
    }

    public function current(): ResultRowInterface
    {
        if ($this->result !== null) {
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
        return $this->result !== null && $this->pointer < $this->numRows;
    }
}
