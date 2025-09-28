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

namespace Tuxxedo\Database\Driver\Sqlite;

use Tuxxedo\Database\Driver\ResultSetInterface;

class SqliteResultSet implements ResultSetInterface
{
    public function __construct(
        private readonly ?\SQLite3Result $result,
        public readonly int $affectedRows = 0,
    ) {
    }

    public function fetchAll(): array
    {
        // @todo Implement fetchAll() method.
    }

    public function fetch(): \Generator
    {
        // @todo Implement fetch() method.
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
