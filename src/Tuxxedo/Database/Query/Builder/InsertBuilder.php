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

class InsertBuilder extends AbstractBuilder implements InsertBuilderInterface
{
    /**
     * @var array<string, string>
     */
    private array $columns = [];

    protected function generateSql(): string
    {
        $columnList = \join(', ', $this->columns);
        $placeholders = \join(', ', \array_keys($this->columns));

        return \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->connection->dialect->identifier($this->table),
            $columnList,
            $placeholders,
        );
    }

    public function set(
        string $column,
        string|int|float|bool|null $value,
    ): static {
        $parameterKey = 'col_' . \count($this->columns);

        $this->columns[':' . $parameterKey] = $this->connection->dialect->identifier($column);
        $this->parameters[$parameterKey] = $value;

        return $this;
    }
}
