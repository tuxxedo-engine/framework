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

use Tuxxedo\Database\Query\Statement\StatementInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;

interface CreateTableStatementInterface extends StatementInterface
{
    public function ifNotExists(): static;

    public function bigInteger(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $autoIncrement = false,
        bool $unique = false,
        int|null $default = null,
    ): ColumnInterface;

    public function blob(
        string $name,
        bool $nullable = false,
    ): ColumnInterface;

    public function boolean(
        string $name,
        bool $nullable = false,
        bool|null $default = null,
    ): ColumnInterface;

    public function char(
        string $name,
        int $length,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface;

    public function date(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface;

    public function dateTime(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface;

    public function decimal(
        string $name,
        int $precision,
        int $scale,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        int|float|null $default = null,
    ): ColumnInterface;

    public function double(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        int|float|null $default = null,
    ): ColumnInterface;

    /**
     * @param list<string> $values
     */
    public function enumeration(
        string $name,
        array $values,
        bool $nullable = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface;

    public function integer(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $autoIncrement = false,
        bool $unique = false,
        int|null $default = null,
    ): ColumnInterface;

    public function json(
        string $name,
        bool $nullable = false,
    ): ColumnInterface;

    public function smallInteger(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $autoIncrement = false,
        bool $unique = false,
        int|null $default = null,
    ): ColumnInterface;

    public function text(
        string $name,
        bool $nullable = false,
    ): ColumnInterface;

    public function time(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface;

    public function timestamp(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface;

    public function tinyInteger(
        string $name,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $autoIncrement = false,
        bool $unique = false,
        int|null $default = null,
    ): ColumnInterface;

    public function varchar(
        string $name,
        int $length,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
    ): ColumnInterface;

    public function raw(
        string $name,
        string $sql,
        bool $nullable = false,
    ): ColumnInterface;
}
