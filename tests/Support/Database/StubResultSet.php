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

namespace Support\Database;

use Tuxxedo\Database\Driver\ResultRowInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Hydrator\HydratorInterface;

class StubResultSet implements ResultSetInterface
{
    public int $affectedRows {
        get {
            return 0;
        }
    }

    public function fetchAll(
        string|\Closure $class = ResultRowInterface::class,
        ?HydratorInterface $hydrator = null,
    ): \Generator {
        yield from [];
    }

    public function fetch(): ResultRowInterface
    {
        throw new \LogicException('StubResultSet: fetch not implemented');
    }

    public function fetchObject(
        string|\Closure $class = ResultRowInterface::class,
        ?HydratorInterface $hydrator = null,
    ): object {
        throw new \LogicException('StubResultSet: fetchObject not implemented');
    }

    public function fetchAssoc(): array
    {
        return [];
    }

    public function fetchRow(): array
    {
        return [];
    }

    public function count(): int
    {
        return 0;
    }

    public function current(): ResultRowInterface
    {
        throw new \LogicException('StubResultSet: current not implemented');
    }

    public function key(): mixed
    {
        return 0;
    }

    public function next(): void
    {
    }

    public function rewind(): void
    {
    }

    public function valid(): bool
    {
        return false;
    }
}
