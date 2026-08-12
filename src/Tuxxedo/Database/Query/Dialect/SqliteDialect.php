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

use Tuxxedo\Database\Query\Parser\StatementParserResult;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\BooleanColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameTable;
use Tuxxedo\Database\SqlException;

class SqliteDialect implements DialectInterface
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
        return '"' . \str_replace('"', '""', $name) . '"';
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
            return 'INTEGER';
        }

        return null;
    }

    public function autoIncrementClause(): string
    {
        return 'PRIMARY KEY AUTOINCREMENT';
    }

    public function interpretBoolean(
        mixed $value,
    ): bool {
        if (\is_string($value)) {
            return $value === '1';
        }

        return (bool) $value;
    }

    public function alterTable(
        string $table,
        array $operations,
    ): array {
        if ($operations === []) {
            return [];
        }

        $tableId = $this->identifier($table);
        $statements = [];

        foreach ($operations as $operation) {
            if ($operation instanceof AddColumn) {
                $statements[] = new StatementParserResult(
                    sql: \sprintf(
                        'ALTER TABLE %s ADD COLUMN %s',
                        $tableId,
                        $operation->column->toSql($this),
                    ),
                );

                continue;
            }

            if ($operation instanceof DropColumn) {
                $statements[] = new StatementParserResult(
                    sql: \sprintf(
                        'ALTER TABLE %s DROP COLUMN %s',
                        $tableId,
                        $this->identifier($operation->name),
                    ),
                );

                continue;
            }

            if ($operation instanceof RenameColumn) {
                $statements[] = new StatementParserResult(
                    sql: \sprintf(
                        'ALTER TABLE %s RENAME COLUMN %s TO %s',
                        $tableId,
                        $this->identifier($operation->from),
                        $this->identifier($operation->to),
                    ),
                );

                continue;
            }

            if ($operation instanceof RenameTable) {
                $statements[] = new StatementParserResult(
                    sql: \sprintf(
                        'ALTER TABLE %s RENAME TO %s',
                        $tableId,
                        $this->identifier($operation->newName),
                    ),
                );

                continue;
            }

            throw SqlException::fromUnsupportedAlterOperation(
                dialect: self::class,
                operation: $operation::class,
            );
        }

        return $statements;
    }

    public function tableExists(
        string $table,
    ): StatementParserResultInterface {
        return new StatementParserResult(
            sql: 'SELECT EXISTS(SELECT 1 FROM sqlite_master WHERE type = \'table\' AND name = :table)',
            parameters: [
                'table' => $table,
            ],
        );
    }

    public function columnExists(
        string $table,
        string $column,
    ): StatementParserResultInterface {
        return new StatementParserResult(
            sql: 'SELECT EXISTS(SELECT 1 FROM pragma_table_info(:table) WHERE name = :column)',
            parameters: [
                'table' => $table,
                'column' => $column,
            ],
        );
    }
}
