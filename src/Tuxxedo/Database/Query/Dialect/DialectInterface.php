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

use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Operation\AlterOperationInterface;
use Tuxxedo\Database\SqlException;

interface DialectInterface
{
    /**
     * @var string[]
     */
    public array $quotations {
        get;
    }

    public function placeholder(
        int $position,
    ): string;

    /**
     * @throws SqlException
     */
    public function identifier(
        string $name,
    ): string;

    public function qualifiedIdentifier(
        string $name,
    ): string;

    public function nativeColumnType(
        ColumnInterface $column,
    ): ?string;

    public function autoIncrementClause(): string;

    public function interpretBoolean(
        mixed $value,
    ): bool;

    /**
     * @param list<AlterOperationInterface> $operations
     * @return list<StatementParserResultInterface>
     *
     * @throws SqlException
     */
    public function alterTable(
        string $table,
        array $operations,
    ): array;

    /**
     * @throws SqlException
     */
    public function tableExists(
        string $table,
    ): StatementParserResultInterface;

    /**
     * @throws SqlException
     */
    public function columnExists(
        string $table,
        string $column,
    ): StatementParserResultInterface;

    /**
     * @throws SqlException
     */
    public function listDatabases(): StatementParserResultInterface;

    /**
     * @throws SqlException
     */
    public function listTables(): StatementParserResultInterface;
}
