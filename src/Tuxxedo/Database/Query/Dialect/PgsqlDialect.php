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

namespace Tuxxedo\Database\Query\Dialect;

use PgSql\Connection;
use Tuxxedo\Database\Query\Statement\Table\Column\BlobColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\DateTimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DoubleColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\JsonColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TinyIntegerColumn;
use Tuxxedo\Database\SqlException;

class PgsqlDialect implements DialectInterface
{
    public private(set) array $quotations = [
        '\'',
        '"',
    ];

    /**
     * @param (\Closure(): Connection)|null $connection
     */
    public function __construct(
        private readonly \Closure|null $connection = null,
    ) {
    }

    public function placeholder(
        int $position,
    ): string {
        return '$' . $position;
    }

    public function identifier(
        string $name,
    ): string {
        if ($this->connection === null) {
            return '"' . \str_replace('"', '""', $name) . '"';
        }

        $quotedName = \pg_escape_identifier(
            ($this->connection)(),
            $name,
        );

        if ($quotedName === false) {
            throw SqlException::fromCannotEscapeIdentifier(
                name: $name,
            );
        }

        return $quotedName;
    }

    public function qualifiedIdentifier(
        string $name,
    ): string {
        return \join(
            '.',
            \array_map(
                fn (string $segment): string => $this->identifier($segment),
                \explode('.', $name),
            ),
        );
    }

    public function nativeColumnType(
        ColumnInterface $column,
    ): ?string {
        if ($column instanceof BlobColumn) {
            return 'BYTEA';
        }

        if ($column instanceof DateTimeColumn) {
            return 'TIMESTAMP';
        }

        if ($column instanceof DoubleColumn) {
            return 'DOUBLE PRECISION';
        }

        if ($column instanceof JsonColumn) {
            return 'JSONB';
        }

        if ($column instanceof TinyIntegerColumn) {
            return 'SMALLINT';
        }

        return null;
    }

    public function autoIncrementClause(): string
    {
        return 'GENERATED ALWAYS AS IDENTITY PRIMARY KEY';
    }
}
