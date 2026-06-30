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

use Tuxxedo\Database\Query\Statement\Table\Column\BooleanColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\EnumerationColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\JsonColumn;

class MysqlDialect implements DialectInterface
{
    public private(set) array $quotations = [
        '\'',
        '"',
        '`',
    ];

    public function placeholder(
        int $position,
    ): string {
        return '?';
    }

    public function identifier(
        string $name,
    ): string {
        return '`' . \str_replace('`', '``', $name) . '`';
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
        if ($column instanceof BooleanColumn) {
            return 'TINYINT(1)';
        }

        if ($column instanceof JsonColumn) {
            return 'JSON';
        }

        if ($column instanceof EnumerationColumn) {
            return \sprintf(
                'ENUM(%s)',
                \join(', ', \array_map(
                    static fn (string $value): string => "'" . \str_replace("'", "''", $value) . "'",
                    $column->values,
                )),
            );
        }

        return null;
    }

    public function autoIncrementClause(): string
    {
        return 'AUTO_INCREMENT PRIMARY KEY';
    }
}
