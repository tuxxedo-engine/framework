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

namespace Tuxxedo\Database\Query\Statement;

use Tuxxedo\Database\Query\Dialect\DialectInterface;

class InsertStatement extends AbstractStatement implements InsertStatementInterface
{
    /**
     * @var array<string, string>
     */
    private array $columns = [];

    protected function generateSql(
        DialectInterface $dialect,
    ): string {
        $quotedColumns = \array_map(
            static fn (string $column): string => $dialect->identifier($column),
            \array_values($this->columns),
        );
        $placeholders = \array_keys($this->columns);

        return \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $dialect->identifier($this->table),
            \join(', ', $quotedColumns),
            \join(', ', $placeholders),
        );
    }

    public function set(
        string $column,
        string|int|float|bool|null $value,
    ): static {
        $parameterKey = 'col_' . \sizeof($this->columns);

        $this->columns[':' . $parameterKey] = $column;
        $this->parameters[$parameterKey] = $value;

        return $this;
    }
}
