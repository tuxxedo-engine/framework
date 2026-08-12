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
use Tuxxedo\Database\Query\Statement\Table\ListDatabasesStatement;

class ListDatabasesStatementTest extends TestCase
{
    public function testCompileDelegatesToDialect(): void
    {
        $dialect = new StubDialect();
        $connection = new StubConnection(
            dialectImpl: $dialect,
        );

        $statement = new ListDatabasesStatement(
            connection: $connection,
        );

        $result = $statement->compile();

        self::assertSame('SELECT database_name', $result->sql);
        self::assertSame(1, $dialect->listDatabasesCalls);
    }

    public function testCompileThrowsWithoutConnection(): void
    {
        $statement = new ListDatabasesStatement();

        try {
            $statement->compile();

            self::fail('Expected DatabaseException to be thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }

    public function testExecuteQueriesConnectionWithDialectSql(): void
    {
        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(),
        );

        $statement = new ListDatabasesStatement(
            connection: $connection,
        );

        $statement->execute();

        self::assertSame(
            [
                'SELECT database_name',
            ],
            $connection->recordedQueries,
        );
    }

    public function testExecuteThrowsWithoutConnection(): void
    {
        $statement = new ListDatabasesStatement();

        try {
            $statement->execute();

            self::fail('Expected DatabaseException to be thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }

    public function testAllReturnsFirstColumnPerRow(): void
    {
        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                rows: [
                    [
                        'main',
                    ],
                    [
                        'analytics',
                    ],
                    [
                        'archive',
                    ],
                ],
            ),
        );

        $statement = new ListDatabasesStatement(
            connection: $connection,
        );

        self::assertSame(
            [
                'main',
                'analytics',
                'archive',
            ],
            $statement->all(),
        );
    }

    public function testAllReturnsEmptyListWhenNoRows(): void
    {
        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(),
        );

        $statement = new ListDatabasesStatement(
            connection: $connection,
        );

        self::assertSame(
            [],
            $statement->all(),
        );
    }

    public function testAllThrowsWithoutConnection(): void
    {
        $statement = new ListDatabasesStatement();

        try {
            $statement->all();

            self::fail('Expected DatabaseException to be thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }
}
