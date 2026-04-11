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

class CountBuilder extends AbstractWhereBuilder implements CountBuilderInterface
{
    private string $column = '*';

    protected function generateSql(): string
    {
        return \sprintf(
            'SELECT COUNT(%s) FROM %s%s',
            $this->column === '*'
                ? '*'
                : $this->connection->dialect->identifier($this->column),
            $this->connection->dialect->identifier($this->table),
            $this->generateWhereSql(),
        );
    }

    public function column(
        string $column = '*',
    ): static {
        $this->column = $column;

        return $this;
    }

    public function count(): int
    {
        /** @var int */
        return $this->execute()->fetchRow()[0];
    }
}
