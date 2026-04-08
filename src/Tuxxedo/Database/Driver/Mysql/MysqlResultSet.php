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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractResultSet;
use Tuxxedo\Database\Driver\HydratableInterface;
use Tuxxedo\Database\Driver\ResultRowInterface;

class MysqlResultSet extends AbstractResultSet
{
    private int $pointer = 0;

    /**
     * @var int<0, max>
     */
    private int $numRows;

    public function __construct(
        protected ContainerInterface $container,
        private ?\mysqli_result $result,
        public readonly int $affectedRows = 0,
    ) {
        if ($this->result !== null) {
            /** @var int<0, max> $numRows */
            $numRows = $this->result->num_rows;

            $this->numRows = $numRows;
        } else {
            $this->numRows = 0;
        }
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
        if ($this->result === null) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = $this->result->fetch_assoc();

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch();
        }

        return parent::hydrate($class, $row);
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
