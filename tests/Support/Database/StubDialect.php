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

namespace Support\Database;

use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResult;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Operation\AlterOperationInterface;

class StubDialect implements DialectInterface
{
    /**
     * @var list<string>
     */
    public private(set) array $quotations;

    public ?string $alterTableTable = null;

    /**
     * @var list<AlterOperationInterface>
     */
    public array $alterTableOperations = [];

    /**
     * @var list<StatementParserResultInterface>
     */
    public array $alterTableResult = [];

    public ?string $tableExistsTable = null;

    public ?string $columnExistsTable = null;

    public ?string $columnExistsColumn = null;

    public int $listDatabasesCalls = 0;

    public int $listTablesCalls = 0;

    public ?string $listIndexesTable = null;

    public ?string $listForeignKeysTable = null;

    public ?string $describeTableTable = null;

    /**
     * @param list<string> $quotations
     */
    public function __construct(
        array $quotations = [
            '\'',
            '"',
        ],
    ) {
        $this->quotations = $quotations;
    }

    public function placeholder(
        int $position,
    ): string {
        return '$' . $position;
    }

    public function identifier(
        string $name,
    ): string {
        return '"' . $name . '"';
    }

    public function qualifiedIdentifier(
        string $name,
    ): string {
        return $this->identifier(
            name: $name,
        );
    }

    public function nativeColumnType(
        ColumnInterface $column,
    ): ?string {
        return null;
    }

    public function autoIncrementClause(): string
    {
        return '';
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
        $this->alterTableTable = $table;
        $this->alterTableOperations = $operations;

        return $this->alterTableResult;
    }

    public function tableExists(
        string $table,
    ): StatementParserResultInterface {
        $this->tableExistsTable = $table;

        return new StatementParserResult(
            sql: 'SELECT 1',
            parameters: [
                'table' => $table,
            ],
        );
    }

    public function columnExists(
        string $table,
        string $column,
    ): StatementParserResultInterface {
        $this->columnExistsTable = $table;
        $this->columnExistsColumn = $column;

        return new StatementParserResult(
            sql: 'SELECT 1',
            parameters: [
                'table' => $table,
                'column' => $column,
            ],
        );
    }

    public function listDatabases(): StatementParserResultInterface
    {
        $this->listDatabasesCalls++;

        return new StatementParserResult(
            sql: 'SELECT database_name',
        );
    }

    public function listTables(): StatementParserResultInterface
    {
        $this->listTablesCalls++;

        return new StatementParserResult(
            sql: 'SELECT table_name',
        );
    }

    public function listIndexes(
        string $table,
    ): StatementParserResultInterface {
        $this->listIndexesTable = $table;

        return new StatementParserResult(
            sql: 'SELECT index_name',
            parameters: [
                'table' => $table,
            ],
        );
    }

    public function listForeignKeys(
        string $table,
    ): StatementParserResultInterface {
        $this->listForeignKeysTable = $table;

        return new StatementParserResult(
            sql: 'SELECT constraint_name',
            parameters: [
                'table' => $table,
            ],
        );
    }

    public function describeTable(
        string $table,
    ): StatementParserResultInterface {
        $this->describeTableTable = $table;

        return new StatementParserResult(
            sql: 'SELECT name',
            parameters: [
                'table' => $table,
            ],
        );
    }
}
