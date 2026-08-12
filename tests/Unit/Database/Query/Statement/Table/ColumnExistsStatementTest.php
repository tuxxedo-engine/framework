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

namespace Unit\Database\Query\Statement\Table;

use PHPUnit\Framework\TestCase;
use Support\Database\StubConnection;
use Support\Database\StubDialect;
use Support\Database\StubResultSet;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Statement\Table\ColumnExistsStatement;

class ColumnExistsStatementTest extends TestCase
{
    public function testCompileDelegatesToDialect(): void
    {
        $dialect = new StubDialect();
        $connection = new StubConnection(
            dialectImpl: $dialect,
        );

        $statement = new ColumnExistsStatement(
            table: 'widgets',
            column: 'quantity',
            connection: $connection,
        );

        $result = $statement->compile();

        self::assertSame('SELECT 1', $result->sql);
        self::assertSame(
            [
                'table' => 'widgets',
                'column' => 'quantity',
            ],
            $result->parameters,
        );
        self::assertSame('widgets', $dialect->columnExistsTable);
        self::assertSame('quantity', $dialect->columnExistsColumn);
    }

    public function testCompileThrowsWithoutConnection(): void
    {
        $statement = new ColumnExistsStatement(
            table: 'widgets',
            column: 'quantity',
        );

        try {
            $statement->compile();

            self::fail('Expected DatabaseException to be thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }

    public function testExecuteQueriesConnectionWithDialectPreparedSql(): void
    {
        $dialect = new StubDialect();
        $connection = new StubConnection(
            dialectImpl: $dialect,
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(),
        );

        $statement = new ColumnExistsStatement(
            table: 'widgets',
            column: 'quantity',
            connection: $connection,
        );

        $statement->execute();

        self::assertSame(
            [
                'SELECT 1',
            ],
            $connection->recordedQueries,
        );
    }

    public function testExecuteThrowsWithoutConnection(): void
    {
        $statement = new ColumnExistsStatement(
            table: 'widgets',
            column: 'quantity',
        );

        try {
            $statement->execute();

            self::fail('Expected DatabaseException to be thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }

    public function testExistsReturnsTrueWhenDialectInterpretsRowAsTrue(): void
    {
        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                firstRow: [
                    '1',
                ],
            ),
        );

        $statement = new ColumnExistsStatement(
            table: 'widgets',
            column: 'quantity',
            connection: $connection,
        );

        self::assertTrue($statement->exists());
    }

    public function testExistsReturnsFalseWhenDialectInterpretsRowAsFalse(): void
    {
        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                firstRow: [
                    '0',
                ],
            ),
        );

        $statement = new ColumnExistsStatement(
            table: 'widgets',
            column: 'quantity',
            connection: $connection,
        );

        self::assertFalse($statement->exists());
    }

    public function testExistsThrowsWithoutConnection(): void
    {
        $statement = new ColumnExistsStatement(
            table: 'widgets',
            column: 'quantity',
        );

        try {
            $statement->exists();

            self::fail('Expected DatabaseException to be thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }
}
