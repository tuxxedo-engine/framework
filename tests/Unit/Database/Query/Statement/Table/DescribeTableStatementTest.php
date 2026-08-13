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
use Tuxxedo\Database\Query\Statement\Table\DescribeTableStatement;

class DescribeTableStatementTest extends TestCase
{
    public function testCompileDelegatesToDialect(): void
    {
        $dialect = new StubDialect();
        $connection = new StubConnection(
            dialectImpl: $dialect,
        );

        $statement = new DescribeTableStatement(
            table: 'users',
            connection: $connection,
        );

        $result = $statement->compile();

        self::assertSame('SELECT name', $result->sql);
        self::assertSame('users', $dialect->describeTableTable);
    }

    public function testCompileThrowsWithoutConnection(): void
    {
        $statement = new DescribeTableStatement(
            table: 'users',
        );

        try {
            $statement->compile();

            self::fail('Expected DatabaseException');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }

    public function testAllFoldsRowsIntoColumnDescriptions(): void
    {
        $rows = [
            [
                'name' => 'id',
                'native_type' => 'int(11)',
                'nullable' => 0,
                'column_default' => null,
                'is_primary' => 1,
                'is_auto_increment' => 1,
            ],
            [
                'name' => 'email',
                'native_type' => 'varchar(190)',
                'nullable' => 0,
                'column_default' => null,
                'is_primary' => 0,
                'is_auto_increment' => 0,
            ],
            [
                'name' => 'status',
                'native_type' => 'varchar(20)',
                'nullable' => 1,
                'column_default' => 'active',
                'is_primary' => 0,
                'is_auto_increment' => 0,
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new DescribeTableStatement(
            table: 'users',
            connection: $connection,
        );

        $columns = $statement->all();

        self::assertCount(3, $columns);

        self::assertSame('id', $columns[0]->name);
        self::assertSame('int(11)', $columns[0]->nativeType);
        self::assertFalse($columns[0]->nullable);
        self::assertNull($columns[0]->default);
        self::assertTrue($columns[0]->primary);
        self::assertTrue($columns[0]->autoIncrement);

        self::assertSame('email', $columns[1]->name);
        self::assertFalse($columns[1]->nullable);
        self::assertFalse($columns[1]->primary);

        self::assertSame('status', $columns[2]->name);
        self::assertTrue($columns[2]->nullable);
        self::assertSame('active', $columns[2]->default);
    }

    public function testByNameReturnsKeyedMap(): void
    {
        $rows = [
            [
                'name' => 'id',
                'native_type' => 'int',
                'nullable' => 0,
                'column_default' => null,
                'is_primary' => 1,
                'is_auto_increment' => 1,
            ],
            [
                'name' => 'email',
                'native_type' => 'varchar(190)',
                'nullable' => 0,
                'column_default' => null,
                'is_primary' => 0,
                'is_auto_increment' => 0,
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new DescribeTableStatement(
            table: 'users',
            connection: $connection,
        );

        $byName = $statement->byName();

        self::assertArrayHasKey('id', $byName);
        self::assertArrayHasKey('email', $byName);
        self::assertSame('id', $byName['id']->name);
    }

    public function testAllReturnsEmptyForNoRows(): void
    {
        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(),
        );

        $statement = new DescribeTableStatement(
            table: 'users',
            connection: $connection,
        );

        self::assertSame([], $statement->all());
    }
}
