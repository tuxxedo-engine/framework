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

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;

class CountStatement extends AbstractWhereStatement implements CountStatementInterface
{
    private string $column = '*';
    private bool $distinct = false;

    protected function generateSql(
        DialectInterface $dialect,
    ): string {
        $countExpression = $this->column === '*'
            ? '*'
            : $dialect->identifier($this->column);

        if ($this->distinct) {
            $countExpression = 'DISTINCT ' . $countExpression;
        }

        return \sprintf(
            'SELECT COUNT(%s) FROM %s%s',
            $countExpression,
            $dialect->identifier($this->table),
            $this->generateWhereSql($dialect),
        );
    }

    public function column(
        string $column = '*',
    ): static {
        $this->column = $column;

        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    public function count(
        ?ConnectionInterface $connection = null,
    ): int {
        /** @var int */
        return $this->execute($connection)->fetchRow()[0];
    }
}
