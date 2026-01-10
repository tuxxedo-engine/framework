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

namespace Tuxxedo\Database\Driver\Mysql;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ResultRow;
use Tuxxedo\Database\Driver\ResultRowInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;

class MysqlResultSet implements ResultSetInterface
{
    private int $pointer = 0;

    /**
     * @var int<0, max>
     */
    private int $numRows;

    public function __construct(
        private ?\mysqli_result $result,
        public readonly int|string $affectedRows = 0,
    ) {
        if ($this->result !== null) {
            /** @var int<0, max>|numeric-string $numRows */
            $numRows = $this->result->num_rows;

            if (\is_string($numRows)) {
                throw DatabaseException::fromResultTooBig();
            }

            $this->numRows = $numRows;
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

        $row = $this->result->fetch_assoc();

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

        $row = $this->result->fetch_array();

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

        $row = $this->result->fetch_assoc();

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

        $row = $this->result->fetch_row();

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch();
        }

        /** @var array<int, mixed> */
        return $row;
    }

    public function free(): void
    {
        if ($this->result !== null) {
            $this->result->free();

            $this->result = null;
            $this->pointer = 0;
            $this->numRows = 0;
        }
    }

    public function count(): int
    {
        return $this->numRows;
    }

    public function current(): ResultRowInterface
    {
        $this->result?->data_seek($this->pointer);

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
