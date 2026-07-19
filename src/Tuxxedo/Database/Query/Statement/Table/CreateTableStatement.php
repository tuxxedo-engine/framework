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

namespace Tuxxedo\Database\Query\Statement\Table;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\BigIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BlobColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BooleanColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\CharColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\DateColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DateTimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DecimalColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DoubleColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\EnumerationColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\JsonColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\RawColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\SmallIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TextColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimestampColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TinyIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\SqlException;

class CreateTableStatement extends AbstractTableStatement implements CreateTableStatementInterface
{
    /**
     * @var list<ColumnInterface>
     */
    private array $columns = [];

    /**
     * @var list<array{columns: list<string>, referencedTable: string, referencedColumns: list<string>, onDelete: ForeignKeyAction|null, onUpdate: ForeignKeyAction|null}>
     */
    private array $foreignKeys = [];

    /**
     * @var list<string>
     */
    private array $primaryKeyColumns = [];

    /**
     * @var list<list<string>>
     */
    private array $uniqueConstraints = [];

    /**
     * @var list<list<string>>
     */
    private array $indexes = [];

    private bool $ifNotExists = false;

    public function ifNotExists(): static
    {
        $this->ifNotExists = true;

        return $this;
    }

    public function bigInteger(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $autoIncrement = false,
        bool $unique = false,
        int|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new BigIntegerColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            autoIncrement: $autoIncrement,
            unique: $unique,
            default: $default,
        );
    }

    public function blob(
        string $name,
        bool $nullable = false,
    ): ColumnInterface {
        return $this->columns[] = new BlobColumn(
            name: $name,
            nullable: $nullable,
        );
    }

    public function boolean(
        string $name,
        bool $nullable = false,
        bool|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new BooleanColumn(
            name: $name,
            nullable: $nullable,
            default: $default,
        );
    }

    public function char(
        string $name,
        int $length,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new CharColumn(
            name: $name,
            length: $length,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    public function date(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new DateColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    public function dateTime(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new DateTimeColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    public function decimal(
        string $name,
        int $precision,
        int $scale,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        int|float|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new DecimalColumn(
            name: $name,
            precision: $precision,
            scale: $scale,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    public function double(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        int|float|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new DoubleColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    /**
     * @param list<string>|class-string<\UnitEnum> $values
     */
    public function enumeration(
        string $name,
        array|string $values,
        bool $nullable = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new EnumerationColumn(
            name: $name,
            values: $values,
            nullable: $nullable,
            unique: $unique,
            default: $default,
        );
    }

    public function integer(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $autoIncrement = false,
        bool $unique = false,
        int|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new IntegerColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            autoIncrement: $autoIncrement,
            unique: $unique,
            default: $default,
        );
    }

    public function json(
        string $name,
        bool $nullable = false,
    ): ColumnInterface {
        return $this->columns[] = new JsonColumn(
            name: $name,
            nullable: $nullable,
        );
    }

    public function smallInteger(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $autoIncrement = false,
        bool $unique = false,
        int|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new SmallIntegerColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            autoIncrement: $autoIncrement,
            unique: $unique,
            default: $default,
        );
    }

    public function text(
        string $name,
        bool $nullable = false,
    ): ColumnInterface {
        return $this->columns[] = new TextColumn(
            name: $name,
            nullable: $nullable,
        );
    }

    public function time(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new TimeColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    public function timestamp(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new TimestampColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    public function tinyInteger(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $autoIncrement = false,
        bool $unique = false,
        int|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new TinyIntegerColumn(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            autoIncrement: $autoIncrement,
            unique: $unique,
            default: $default,
        );
    }

    public function varchar(
        string $name,
        int $length,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface {
        return $this->columns[] = new VarcharColumn(
            name: $name,
            length: $length,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    public function raw(
        string $name,
        string $sql,
        bool $nullable = false,
    ): ColumnInterface {
        return $this->columns[] = new RawColumn(
            name: $name,
            sql: $sql,
            nullable: $nullable,
        );
    }

    public function foreignKey(
        array $columns,
        string $referencedTable,
        array $referencedColumns,
        ?ForeignKeyAction $onDelete = null,
        ?ForeignKeyAction $onUpdate = null,
    ): static {
        $this->foreignKeys[] = [
            'columns' => $columns,
            'referencedTable' => $referencedTable,
            'referencedColumns' => $referencedColumns,
            'onDelete' => $onDelete,
            'onUpdate' => $onUpdate,
        ];

        return $this;
    }

    public function primaryKey(
        string ...$columns,
    ): static {
        $this->primaryKeyColumns = \array_values($columns);

        return $this;
    }

    public function unique(
        string ...$columns,
    ): static {
        $this->uniqueConstraints[] = \array_values($columns);

        return $this;
    }

    public function index(
        string ...$columns,
    ): static {
        $this->indexes[] = \array_values($columns);

        return $this;
    }

    protected function generateSql(
        DialectInterface $dialect,
    ): string {
        if (\sizeof($this->columns) === 0) {
            throw SqlException::fromCreateTableWithoutColumns(
                table: $this->table,
            );
        }

        $definitions = [];

        foreach ($this->columns as $column) {
            $definitions[] = $column->toSql($dialect);
        }

        if (\sizeof($this->primaryKeyColumns) > 0) {
            $definitions[] = \sprintf(
                'PRIMARY KEY (%s)',
                \join(
                    ', ',
                    \array_map(
                        static fn (string $c): string => $dialect->identifier($c),
                        $this->primaryKeyColumns,
                    ),
                ),
            );
        }

        foreach ($this->uniqueConstraints as $uniqueColumns) {
            $definitions[] = \sprintf(
                'UNIQUE (%s)',
                \join(
                    ', ',
                    \array_map(
                        static fn (string $c): string => $dialect->identifier($c),
                        $uniqueColumns,
                    ),
                ),
            );
        }

        foreach ($this->foreignKeys as $foreignKey) {
            $clause = \sprintf(
                'FOREIGN KEY (%s) REFERENCES %s (%s)',
                \join(
                    ', ',
                    \array_map(
                        static fn (string $c): string => $dialect->identifier($c),
                        $foreignKey['columns'],
                    ),
                ),
                $dialect->identifier($foreignKey['referencedTable']),
                \join(
                    ', ',
                    \array_map(
                        static fn (string $c): string => $dialect->identifier($c),
                        $foreignKey['referencedColumns'],
                    ),
                ),
            );

            if ($foreignKey['onDelete'] !== null) {
                $clause .= ' ON DELETE ' . $foreignKey['onDelete']->value;
            }

            if ($foreignKey['onUpdate'] !== null) {
                $clause .= ' ON UPDATE ' . $foreignKey['onUpdate']->value;
            }

            $definitions[] = $clause;
        }

        return \sprintf(
            'CREATE TABLE %s%s (%s)',
            $this->ifNotExists
                ? 'IF NOT EXISTS '
                : '',
            $dialect->identifier($this->table),
            \join(', ', $definitions),
        );
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $connection ?? $this->connection;

        if ($resolvedConnection === null) {
            throw DatabaseException::fromNoConnectionAvailable();
        }

        $result = $resolvedConnection->query(
            sql: $this->generateSql($resolvedConnection->dialect),
            native: true,
        );

        foreach ($this->indexes as $indexColumns) {
            $resolvedConnection->query(
                sql: $this->generateIndexSql(
                    dialect: $resolvedConnection->dialect,
                    columns: $indexColumns,
                ),
                native: true,
            );
        }

        return $result;
    }

    /**
     * @param list<string> $columns
     */
    private function generateIndexSql(
        DialectInterface $dialect,
        array $columns,
    ): string {
        $indexName = $this->table . '_' . \join('_', $columns) . '_idx';

        return \sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $dialect->identifier($indexName),
            $dialect->identifier($this->table),
            \join(
                ', ',
                \array_map(
                    static fn (string $c): string => $dialect->identifier($c),
                    $columns,
                ),
            ),
        );
    }
}
