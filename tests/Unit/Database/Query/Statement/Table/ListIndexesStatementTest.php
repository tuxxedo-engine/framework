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
use Tuxxedo\Database\Query\Statement\Table\ListIndexesStatement;

class ListIndexesStatementTest extends TestCase
{
    public function testCompileDelegatesToDialect(): void
    {
        $dialect = new StubDialect();
        $connection = new StubConnection(
            dialectImpl: $dialect,
        );

        $statement = new ListIndexesStatement(
            table: 'users',
            connection: $connection,
        );

        $result = $statement->compile();

        self::assertSame('SELECT index_name', $result->sql);
        self::assertSame('users', $dialect->listIndexesTable);
    }

    public function testCompileThrowsWithoutConnection(): void
    {
        $statement = new ListIndexesStatement(
            table: 'users',
        );

        try {
            $statement->compile();

            self::fail('Expected DatabaseException');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }

    public function testAllFoldsRowsIntoMetadataObjects(): void
    {
        $rows = [
            [
                'index_name' => 'PRIMARY',
                'column_name' => 'id',
                'is_unique' => 1,
                'is_primary' => 1,
            ],
            [
                'index_name' => 'idx_email',
                'column_name' => 'email',
                'is_unique' => 1,
                'is_primary' => 0,
            ],
            [
                'index_name' => 'idx_name_email',
                'column_name' => 'name',
                'is_unique' => 0,
                'is_primary' => 0,
            ],
            [
                'index_name' => 'idx_name_email',
                'column_name' => 'email',
                'is_unique' => 0,
                'is_primary' => 0,
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new ListIndexesStatement(
            table: 'users',
            connection: $connection,
        );

        $indexes = $statement->all();

        self::assertCount(3, $indexes);
        self::assertSame('PRIMARY', $indexes[0]->name);
        self::assertTrue($indexes[0]->primary);
        self::assertSame(
            [
                'id',
            ],
            $indexes[0]->columns,
        );

        self::assertSame('idx_email', $indexes[1]->name);
        self::assertTrue($indexes[1]->unique);
        self::assertFalse($indexes[1]->primary);

        self::assertSame('idx_name_email', $indexes[2]->name);
        self::assertFalse($indexes[2]->unique);
        self::assertSame(
            [
                'name',
                'email',
            ],
            $indexes[2]->columns,
        );
    }

    public function testByNameReturnsKeyedMap(): void
    {
        $rows = [
            [
                'index_name' => 'idx_email',
                'column_name' => 'email',
                'is_unique' => 1,
                'is_primary' => 0,
            ],
            [
                'index_name' => 'PRIMARY',
                'column_name' => 'id',
                'is_unique' => 1,
                'is_primary' => 1,
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new ListIndexesStatement(
            table: 'users',
            connection: $connection,
        );

        $byName = $statement->byName();

        self::assertArrayHasKey('idx_email', $byName);
        self::assertArrayHasKey('PRIMARY', $byName);
        self::assertSame('idx_email', $byName['idx_email']->name);
        self::assertTrue($byName['PRIMARY']->primary);
    }

    public function testAllReturnsEmptyForNoRows(): void
    {
        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(),
        );

        $statement = new ListIndexesStatement(
            table: 'users',
            connection: $connection,
        );

        self::assertSame([], $statement->all());
    }
}
