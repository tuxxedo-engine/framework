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
use Tuxxedo\Database\Query\Statement\Table\Column\EnumerationColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\JsonColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddIndex;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddPrimaryKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddUnique;
use Tuxxedo\Database\Query\Statement\Table\Operation\ChangeColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropIndex;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropPrimaryKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropUnique;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameTable;
use Tuxxedo\Database\SqlException;

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
        $clauses = [];
        $extra = [];

        foreach ($operations as $operation) {
            if ($operation instanceof AddColumn) {
                $clauses[] = 'ADD COLUMN ' . $operation->column->toSql($this);

                continue;
            }

            if ($operation instanceof DropColumn) {
                $clauses[] = \sprintf(
                    'DROP COLUMN%s %s',
                    $operation->ifExists
                        ? ' IF EXISTS'
                        : '',
                    $this->identifier($operation->name),
                );

                continue;
            }

            if ($operation instanceof RenameColumn) {
                $clauses[] = \sprintf(
                    'RENAME COLUMN %s TO %s',
                    $this->identifier($operation->from),
                    $this->identifier($operation->to),
                );

                continue;
            }

            if ($operation instanceof ChangeColumn) {
                $clauses[] = 'MODIFY COLUMN ' . $operation->column->toSql($this);

                continue;
            }

            if ($operation instanceof RenameTable) {
                $clauses[] = 'RENAME TO ' . $this->identifier($operation->newName);

                continue;
            }

            if ($operation instanceof AddIndex) {
                $extra[] = \sprintf(
                    'CREATE INDEX %s ON %s (%s)',
                    $this->identifier($operation->name ?? self::defaultIndexName($table, $operation->columns)),
                    $tableId,
                    $this->joinColumns($operation->columns),
                );

                continue;
            }

            if ($operation instanceof DropIndex) {
                $extra[] = \sprintf(
                    'DROP INDEX %s ON %s',
                    $this->identifier($operation->name),
                    $tableId,
                );

                continue;
            }

            if ($operation instanceof AddUnique) {
                $clauses[] = \sprintf(
                    'ADD CONSTRAINT %s UNIQUE (%s)',
                    $this->identifier($operation->name ?? self::defaultUniqueName($table, $operation->columns)),
                    $this->joinColumns($operation->columns),
                );

                continue;
            }

            if ($operation instanceof DropUnique) {
                $clauses[] = 'DROP INDEX ' . $this->identifier($operation->name);

                continue;
            }

            if ($operation instanceof AddForeignKey) {
                $clause = \sprintf(
                    'ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
                    $this->identifier($operation->name ?? self::defaultForeignKeyName($table, $operation->columns)),
                    $this->joinColumns($operation->columns),
                    $this->identifier($operation->referencedTable),
                    $this->joinColumns($operation->referencedColumns),
                );

                if ($operation->onDelete !== null) {
                    $clause .= ' ON DELETE ' . $operation->onDelete->value;
                }

                if ($operation->onUpdate !== null) {
                    $clause .= ' ON UPDATE ' . $operation->onUpdate->value;
                }

                $clauses[] = $clause;

                continue;
            }

            if ($operation instanceof DropForeignKey) {
                $clauses[] = 'DROP FOREIGN KEY ' . $this->identifier($operation->name);

                continue;
            }

            if ($operation instanceof AddPrimaryKey) {
                $clauses[] = \sprintf(
                    'ADD PRIMARY KEY (%s)',
                    $this->joinColumns($operation->columns),
                );

                continue;
            }

            if ($operation instanceof DropPrimaryKey) {
                $clauses[] = 'DROP PRIMARY KEY';

                continue;
            }

            throw SqlException::fromUnsupportedAlterOperation(
                dialect: self::class,
                operation: $operation::class,
            );
        }

        $statements = [];

        if ($clauses !== []) {
            $statements[] = new StatementParserResult(
                sql: \sprintf(
                    'ALTER TABLE %s %s',
                    $tableId,
                    \join(', ', $clauses),
                ),
            );
        }

        foreach ($extra as $statement) {
            $statements[] = new StatementParserResult(
                sql: $statement,
            );
        }

        return $statements;
    }

    public function tableExists(
        string $table,
    ): StatementParserResultInterface {
        return new StatementParserResult(
            sql: 'SELECT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table)',
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
            sql: 'SELECT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column)',
            parameters: [
                'table' => $table,
                'column' => $column,
            ],
        );
    }

    public function listDatabases(): StatementParserResultInterface
    {
        return new StatementParserResult(
            sql: 'SELECT schema_name FROM information_schema.schemata ORDER BY schema_name',
        );
    }

    public function listTables(): StatementParserResultInterface
    {
        return new StatementParserResult(
            sql: 'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name',
        );
    }

    /**
     * @param list<string> $columns
     */
    private function joinColumns(
        array $columns,
    ): string {
        return \join(
            ', ',
            \array_map(
                fn (string $column): string => $this->identifier($column),
                $columns,
            ),
        );
    }

    /**
     * @param list<string> $columns
     */
    private static function defaultIndexName(
        string $table,
        array $columns,
    ): string {
        return $table . '_' . \join('_', $columns) . '_idx';
    }

    /**
     * @param list<string> $columns
     */
    private static function defaultUniqueName(
        string $table,
        array $columns,
    ): string {
        return $table . '_' . \join('_', $columns) . '_unq';
    }

    /**
     * @param list<string> $columns
     */
    private static function defaultForeignKeyName(
        string $table,
        array $columns,
    ): string {
        return $table . '_' . \join('_', $columns) . '_fk';
    }
}
