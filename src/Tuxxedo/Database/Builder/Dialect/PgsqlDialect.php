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

namespace Tuxxedo\Database\Builder\Dialect;

use PgSql\Connection;
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

    public function placeholder(int $position): string
    {
        return '$' . $position;
    }

    public function identifier(string $name): string
    {
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
}
