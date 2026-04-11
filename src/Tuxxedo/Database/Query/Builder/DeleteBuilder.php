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

class DeleteBuilder extends AbstractWhereBuilder implements DeleteBuilderInterface
{
    private ?int $limit = null;

    protected function generateSql(): string
    {
        $sql = \sprintf(
            'DELETE FROM %s%s',
            $this->connection->dialect->identifier($this->table),
            $this->generateWhereSql(),
        );

        if ($this->limit !== null) {
            $sql .= \sprintf(
                ' LIMIT %d',
                $this->limit,
            );
        }

        return $sql;
    }

    public function limit(
        int $limit,
    ): static {
        $this->limit = $limit;

        return $this;
    }
}
