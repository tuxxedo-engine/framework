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
use Tuxxedo\Database\Driver\ResultRowInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;

class PgsqlResultSet implements ResultSetInterface
{
    public function __construct(
        private readonly ?Result $result,
        public readonly int $affectedRows = 0,
    ) {
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
        // TODO: Implement fetchObject() method.
    }

    public function fetchArray(): array
    {
        // TODO: Implement fetchArray() method.
    }

    public function fetchAssoc(): array
    {
        // TODO: Implement fetchAssoc() method.
    }

    public function fetchRow(): array
    {
        // TODO: Implement fetchRow() method.
    }

    public function free(): void
    {
    }

    public function count(): int
    {
        // @todo Implement count() method.
    }

    public function current(): mixed
    {
        // @todo Implement current() method.
    }

    public function key(): mixed
    {
        // @todo Implement key() method.
    }

    public function next(): void
    {
        // @todo Implement next() method.
    }

    public function rewind(): void
    {
        // @todo Implement rewind() method.
    }

    public function valid(): bool
    {
        // @todo Implement valid() method.
    }
}
