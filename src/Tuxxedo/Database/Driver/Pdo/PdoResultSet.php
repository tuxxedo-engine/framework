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

namespace Tuxxedo\Database\Driver\Pdo;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ResultRow;
use Tuxxedo\Database\Driver\ResultRowInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;

class PdoResultSet implements ResultSetInterface
{
    private int $pointer = 0;
    private bool $endedBuffering = false;

    /**
     * @var array<int, mixed[]>
     */
    private array $buffer = [];

    public function __construct(
        private readonly ?\PDOStatement $result,
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

        $row = $this->result->fetch(\PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        return $row;
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

        if ($this->endedBuffering || $this->increaseBuffer()) {
            if (!\array_key_exists($this->pointer, $this->buffer)) {
                throw DatabaseException::fromCannotFetch();
            }

            return new ResultRow(
                properties: $this->buffer[$this->pointer++],
            );
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

            /** @var array<int, mixed> */
            return $this->buffer[$this->pointer++];
        }

        $this->endedBuffering = true;

        throw DatabaseException::fromCannotFetch();
    }

    public function free(): void
    {
        $this->pointer = 0;
        $this->buffer = [];
        $this->endedBuffering = false;
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
