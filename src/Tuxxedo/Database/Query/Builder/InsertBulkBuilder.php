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

namespace Tuxxedo\Database\Query\Builder;

use Tuxxedo\Database\SqlException;

class InsertBulkBuilder extends AbstractBuilder implements InsertBulkBuilderInterface
{
    /**
     * @var array<string, string>
     */
    private array $columns = [];

    /**
     * @var array<string, string>
     */
    private array $columnMap = [];

    /**
     * @var int<0, max>
     */
    private int $rowCount = 0;

    protected function generateSql(): string
    {
        $rowPlaceholders = [];
        $columnList = \join(', ', $this->columns);

        for ($i = 0; $i < $this->rowCount; $i++) {
            $slots = \array_map(
                static fn (string $key): string => $key . '_' . $i,
                \array_keys($this->columns),
            );

            $rowPlaceholders[] = '(' . \join(', ', $slots) . ')';
        }

        return \sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->connection->dialect->identifier($this->table),
            $columnList,
            \join(', ', $rowPlaceholders),
        );
    }

    /**
     * @param non-empty-array<string, string|int|float|bool|null> ...$rows
     *
     * @throws SqlException
     */
    public function values(
        array ...$rows,
    ): static {
        $expectedColumns = \array_keys($rows[0]);
        $expectedSize = \sizeof($expectedColumns);

        if (\sizeof($this->columns) > 0) {
            $expectedColumns = \array_keys($this->columnMap);
            $expectedSize = \sizeof($expectedColumns);
        }

        foreach ($rows as $row) {
            $actualColumns = \array_keys($row);
            $rowSize = \sizeof($row);

            if (
                $rowSize !== $expectedSize ||
                $actualColumns !== $expectedColumns
            ) {
                throw SqlException::fromUnexpectedInsertBulkSize(
                    rows: $rowSize,
                    expectedRows: $expectedSize,
                );
            }
        }

        if (\sizeof($this->columns) === 0) {
            foreach ($expectedColumns as $column) {
                $paramKey = 'col_' . \sizeof($this->columns);

                $this->columns[':' . $paramKey] = $this->connection->dialect->identifier($column);
                $this->columnMap[$paramKey] = $column;
            }
        }

        $index = 0;
        $offset = $this->rowCount;
        $this->rowCount += \sizeof($rows);

        foreach ($rows as $row) {
            foreach ($this->columnMap as $paramKey => $originalColumn) {
                $this->parameters[$paramKey . '_' . ($offset + $index)] = $row[$originalColumn];
            }

            $index++;
        }

        return $this;
    }
}
