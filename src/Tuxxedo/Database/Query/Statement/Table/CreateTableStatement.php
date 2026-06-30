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

    public function enumeration(
        string $name,
        array $values,
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

        return \sprintf(
            'CREATE TABLE %s%s (%s)',
            $this->ifNotExists
                ? 'IF NOT EXISTS '
                : '',
            $dialect->identifier($this->table),
            \join(', ', $definitions),
        );
    }
}
