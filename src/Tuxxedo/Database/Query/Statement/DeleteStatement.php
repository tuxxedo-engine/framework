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

class DeleteStatement extends AbstractWhereStatement implements DeleteStatementInterface
{
    private ?int $limit = null;

    protected function generateSql(
        DialectInterface $dialect,
    ): string {
        $sql = \sprintf(
            '%sDELETE FROM %s%s',
            $this->generateWithSql($dialect),
            $dialect->identifier($this->table),
            $this->generateWhereSql($dialect),
        );

        if ($this->limit !== null) {
            $sql .= \sprintf(
                ' LIMIT %d',
                $this->limit,
            );
        }

        return $sql;
    }

    public function with(
        string $name,
        SubqueryRenderableInterface $query,
        array $columns = [],
    ): static {
        $this->addCommonTableExpression(
            name: $name,
            query: $query,
            columns: $columns,
        );

        return $this;
    }

    public function withRecursive(
        string $name,
        SubqueryRenderableInterface $anchor,
        SubqueryRenderableInterface $recursive,
        array $columns = [],
        bool $distinct = false,
    ): static {
        $this->addCommonTableExpression(
            name: $name,
            query: $anchor,
            recursive: $recursive,
            columns: $columns,
            distinct: $distinct,
        );

        return $this;
    }

    public function limit(
        int $limit,
    ): static {
        $this->limit = $limit;

        return $this;
    }
}
