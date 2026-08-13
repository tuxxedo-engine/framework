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

    /**
     * @var list<array<int, mixed>>
     */
    private array $rows;

    /**
     * @var list<array<string, mixed>>
     */
    private array $assocRows;

    private int $cursor = 0;

    private int $assocCursor = 0;

    /**
     * @param list<array<int, mixed>> $rows
     * @param array<int, mixed>|null $firstRow
     * @param list<array<string, mixed>> $assocRows
     */
    public function __construct(
        array $rows = [],
        ?array $firstRow = null,
        array $assocRows = [],
    ) {
        if ($firstRow !== null) {
            $this->rows = [
                $firstRow,
            ];
            $this->assocRows = $assocRows;

            return;
        }

        $this->rows = $rows;
        $this->assocRows = $assocRows;
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
        if ($this->assocCursor >= \sizeof($this->assocRows)) {
            return [];
        }

        return $this->assocRows[$this->assocCursor++];
    }

    public function fetchRow(): array
    {
        if ($this->cursor >= \sizeof($this->rows)) {
            return [];
        }

        return $this->rows[$this->cursor++];
    }

    public function count(): int
    {
        return \sizeof($this->rows) > 0
            ? \sizeof($this->rows)
            : \sizeof($this->assocRows);
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
