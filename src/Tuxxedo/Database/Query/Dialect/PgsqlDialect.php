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
use Tuxxedo\Database\Query\Parser\StatementParserResult;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\AbstractColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BlobColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\DateTimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DoubleColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\JsonColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TinyIntegerColumn;
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
        private readonly bool $usePositionalPlaceholders = false,
    ) {
    }

    public function placeholder(
        int $position,
    ): string {
        if ($this->usePositionalPlaceholders) {
            return '?';
        }

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
            // @codeCoverageIgnoreStart
            throw SqlException::fromCannotEscapeIdentifier(
                name: $name,
            );
            // @codeCoverageIgnoreEnd
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

    public function interpretBoolean(
        mixed $value,
    ): bool {
        if (\is_string($value)) {
            return $value === 't' || $value === '1';
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
                $statements[] = \sprintf(
                    'ALTER TABLE %s ADD COLUMN %s',
                    $tableId,
                    $operation->column->toSql($this),
                );

                continue;
            }

            if ($operation instanceof DropColumn) {
                $statements[] = \sprintf(
                    'ALTER TABLE %s DROP COLUMN%s %s',
                    $tableId,
                    $operation->ifExists
                        ? ' IF EXISTS'
                        : '',
                    $this->identifier($operation->name),
                );

                continue;
            }

            if ($operation instanceof RenameColumn) {
                $statements[] = \sprintf(
                    'ALTER TABLE %s RENAME COLUMN %s TO %s',
                    $tableId,
                    $this->identifier($operation->from),
                    $this->identifier($operation->to),
                );

                continue;
            }

            if ($operation instanceof ChangeColumn) {
                foreach ($this->compileChangeColumn($tableId, $operation) as $statement) {
                    $statements[] = $statement;
                }

                continue;
            }

            if ($operation instanceof RenameTable) {
                $statements[] = \sprintf(
                    'ALTER TABLE %s RENAME TO %s',
                    $tableId,
                    $this->identifier($operation->newName),
                );

                continue;
            }

            if ($operation instanceof AddIndex) {
                $statements[] = \sprintf(
                    'CREATE INDEX %s ON %s (%s)',
                    $this->identifier($operation->name ?? self::defaultIndexName($table, $operation->columns)),
                    $tableId,
                    $this->joinColumns($operation->columns),
                );

                continue;
            }

            if ($operation instanceof DropIndex) {
                $statements[] = 'DROP INDEX ' . $this->identifier($operation->name);

                continue;
            }

            if ($operation instanceof AddUnique) {
                $statements[] = \sprintf(
                    'ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (%s)',
                    $tableId,
                    $this->identifier($operation->name ?? self::defaultUniqueName($table, $operation->columns)),
                    $this->joinColumns($operation->columns),
                );

                continue;
            }

            if ($operation instanceof DropUnique) {
                $statements[] = \sprintf(
                    'ALTER TABLE %s DROP CONSTRAINT %s',
                    $tableId,
                    $this->identifier($operation->name),
                );

                continue;
            }

            if ($operation instanceof AddForeignKey) {
                $clause = \sprintf(
                    'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
                    $tableId,
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

                $statements[] = $clause;

                continue;
            }

            if ($operation instanceof DropForeignKey) {
                $statements[] = \sprintf(
                    'ALTER TABLE %s DROP CONSTRAINT %s',
                    $tableId,
                    $this->identifier($operation->name),
                );

                continue;
            }

            if ($operation instanceof AddPrimaryKey) {
                $statements[] = \sprintf(
                    'ALTER TABLE %s ADD PRIMARY KEY (%s)',
                    $tableId,
                    $this->joinColumns($operation->columns),
                );

                continue;
            }

            if ($operation instanceof DropPrimaryKey) {
                $statements[] = \sprintf(
                    'ALTER TABLE %s DROP CONSTRAINT %s',
                    $tableId,
                    $this->identifier($table . '_pkey'),
                );

                continue;
            }

            throw SqlException::fromUnsupportedAlterOperation(
                dialect: self::class,
                operation: $operation::class,
            );
        }

        $results = [];

        foreach ($statements as $sql) {
            $results[] = new StatementParserResult(
                sql: $sql,
            );
        }

        return $results;
    }

    public function tableExists(
        string $table,
    ): StatementParserResultInterface {
        return new StatementParserResult(
            sql: 'SELECT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :table)',
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
            sql: 'SELECT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = :table AND column_name = :column)',
            parameters: [
                'table' => $table,
                'column' => $column,
            ],
        );
    }

    /**
     * @return list<string>
     *
     * @throws SqlException
     */
    private function compileChangeColumn(
        string $tableId,
        ChangeColumn $operation,
    ): array {
        $column = $operation->column;

        if (!$column instanceof AbstractColumn) {
            throw SqlException::fromUnsupportedAlterOperation(
                dialect: self::class,
                operation: ChangeColumn::class,
            );
        }

        $columnId = $this->identifier($column->name);
        $type = $this->nativeColumnType($column) ?? $column->typeString($this);

        $typeClause = \sprintf(
            'ALTER TABLE %s ALTER COLUMN %s TYPE %s',
            $tableId,
            $columnId,
            $type,
        );

        if ($operation->using !== null) {
            $typeClause .= ' USING ' . $operation->using;
        }

        $statements = [
            $typeClause,
            \sprintf(
                'ALTER TABLE %s ALTER COLUMN %s %s NOT NULL',
                $tableId,
                $columnId,
                $column->nullable
                    ? 'DROP'
                    : 'SET',
            ),
        ];

        if ($column->default !== null) {
            $statements[] = \sprintf(
                'ALTER TABLE %s ALTER COLUMN %s SET DEFAULT %s',
                $tableId,
                $columnId,
                self::renderDefault($column->default),
            );
        } else {
            $statements[] = \sprintf(
                'ALTER TABLE %s ALTER COLUMN %s DROP DEFAULT',
                $tableId,
                $columnId,
            );
        }

        return $statements;
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

    private static function renderDefault(
        string|int|float|bool $default,
    ): string {
        if (\is_bool($default)) {
            return $default
                ? 'TRUE'
                : 'FALSE';
        }

        if (\is_int($default) || \is_float($default)) {
            return (string) $default;
        }

        return "'" . \str_replace("'", "''", $default) . "'";
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
