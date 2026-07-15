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
use Tuxxedo\Database\Driver\ResultRowInterface;
use Tuxxedo\Database\Hydrator\HydratableInterface;
use Tuxxedo\Database\Hydrator\HydratorInterface;

class PgsqlResultSet extends AbstractResultSet
{
    private int $pointer = 0;
    private int $numRows;

    /**
     * @var array<int, string>
     */
    private array $columnTypes;

    public function __construct(
        protected ContainerInterface $container,
        private Result $result,
        public readonly int $affectedRows = 0,
    ) {
        $this->numRows = \pg_num_rows($this->result);
        $this->columnTypes = $this->buildColumnTypeMap();
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return TClassName
     */
    public function fetchObject(
        string|\Closure $class = ResultRowInterface::class,
        ?HydratorInterface $hydrator = null,
    ): object {
        if ($this->numRows === 0) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_assoc($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch(); // @codeCoverageIgnore
        }

        return parent::hydrate($class, $this->coerceRow($row), $hydrator);
    }

    public function fetchAssoc(): array
    {
        if ($this->numRows === 0) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_assoc($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch(); // @codeCoverageIgnore
        }

        return $this->coerceRow($row);
    }

    public function fetchRow(): array
    {
        if ($this->numRows === 0) {
            throw DatabaseException::fromEmptyResultSet();
        }

        $row = \pg_fetch_row($this->result);

        if (!\is_array($row)) {
            throw DatabaseException::fromCannotFetch(); // @codeCoverageIgnore
        }

        return \array_values($this->coerceRow($row));
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

    /**
     * @return array<int, string>
     */
    private function buildColumnTypeMap(): array
    {
        $types = [];
        $columnCount = \pg_num_fields($this->result);

        for ($i = 0; $i < $columnCount; $i++) {
            $types[$i] = \pg_field_type($this->result, $i);
        }

        return $types;
    }

    /**
     * @template TKey of array-key
     *
     * @param array<TKey, string|null> $row
     * @return array<TKey, mixed>
     */
    private function coerceRow(
        array $row,
    ): array {
        $coerced = [];
        $index = 0;

        foreach ($row as $key => $value) {
            $coerced[$key] = $this->coerceValue(
                value: $value,
                type: $this->columnTypes[$index] ?? '',
            );

            $index++;
        }

        return $coerced;
    }

    private function coerceValue(
        ?string $value,
        string $type,
    ): mixed {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int2', 'int4', 'int8' => (int) $value,
            'float4', 'float8', 'numeric' => (float) $value,
            'bool' => $value === 't',
            'bytea' => \pg_unescape_bytea($value),
            default => $value,
        };
    }
}
