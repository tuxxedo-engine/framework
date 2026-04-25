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

namespace Tuxxedo\Database\Driver\Sqlite;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractResultSet;
use Tuxxedo\Database\Driver\HydratableInterface;
use Tuxxedo\Database\Driver\ResultRow;
use Tuxxedo\Database\Driver\ResultRowInterface;

class SqliteResultSet extends AbstractResultSet
{
    private int $pointer = 0;
    private bool $endedBuffering = false;

    /**
     * @var array<int, mixed[]>
     */
    private array $buffer = [];

    public function __construct(
        protected ContainerInterface $container,
        private ?\SQLite3Result $result,
        public readonly int $affectedRows = 0,
    ) {
    }

    private function increaseBuffer(): bool
    {
        if ($this->endedBuffering) {
            return false;
        }

        $next = $this->fetchNext();

        if ($next === null) {
            $this->endedBuffering = true;

            return false;
        }

        $this->buffer[] = $next;

        return true;
    }

    /**
     * @return mixed[]|null
     */
    private function fetchNext(): ?array
    {
        if ($this->result === null) {
            return null;
        }

        $row = $this->result->fetchArray(\SQLITE3_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return TClassName
     */
    public function fetchObject(
        string|\Closure $class = ResultRowInterface::class,
    ): object {
        if ($this->result === null) {
            throw DatabaseException::fromEmptyResultSet();
        }

        if ($this->endedBuffering || $this->increaseBuffer()) {
            if (!\array_key_exists($this->pointer, $this->buffer)) {
                throw DatabaseException::fromCannotFetch();
            }

            return parent::hydrate($class, $this->buffer[$this->pointer++]);
        }

        $this->endedBuffering = true;

        throw DatabaseException::fromCannotFetch();
    }

    public function fetchArray(): array
    {
        if ($this->result === null) {
            throw DatabaseException::fromEmptyResultSet();
        }

        if ($this->endedBuffering || $this->increaseBuffer()) {
            if (!\array_key_exists($this->pointer, $this->buffer)) {
                throw DatabaseException::fromCannotFetch();
            }

            return $this->buffer[$this->pointer++];
        }

        $this->endedBuffering = true;

        throw DatabaseException::fromCannotFetch();
    }

    public function fetchAssoc(): array
    {
        if ($this->result === null) {
            throw DatabaseException::fromEmptyResultSet();
        }

        if ($this->endedBuffering || $this->increaseBuffer()) {
            if (!\array_key_exists($this->pointer, $this->buffer)) {
                throw DatabaseException::fromCannotFetch();
            }

            return $this->buffer[$this->pointer++];
        }

        $this->endedBuffering = true;

        throw DatabaseException::fromCannotFetch();
    }

    public function fetchRow(): array
    {
        if ($this->result === null) {
            throw DatabaseException::fromEmptyResultSet();
        }

        if ($this->endedBuffering || $this->increaseBuffer()) {
            if (!\array_key_exists($this->pointer, $this->buffer)) {
                throw DatabaseException::fromCannotFetch();
            }

            return \array_values($this->buffer[$this->pointer++]);
        }

        $this->endedBuffering = true;

        throw DatabaseException::fromCannotFetch();
    }

    public function count(): int
    {
        if ($this->endedBuffering) {
            return \sizeof($this->buffer);
        }

        while ($this->increaseBuffer()) {
        }

        return \sizeof($this->buffer);
    }

    public function current(): ResultRowInterface
    {
        return new ResultRow(
            properties: $this->buffer[$this->pointer],
        );
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
        if ($this->increaseBuffer()) {
            return true;
        }

        return $this->pointer < \sizeof($this->buffer);
    }
}
