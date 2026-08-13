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
use Tuxxedo\Database\Query\Statement\Table\ForeignKeyAction;
use Tuxxedo\Database\Query\Statement\Table\ListForeignKeysStatement;

class ListForeignKeysStatementTest extends TestCase
{
    public function testCompileDelegatesToDialect(): void
    {
        $dialect = new StubDialect();
        $connection = new StubConnection(
            dialectImpl: $dialect,
        );

        $statement = new ListForeignKeysStatement(
            table: 'orders',
            connection: $connection,
        );

        $result = $statement->compile();

        self::assertSame('SELECT constraint_name', $result->sql);
        self::assertSame('orders', $dialect->listForeignKeysTable);
    }

    public function testCompileThrowsWithoutConnection(): void
    {
        $statement = new ListForeignKeysStatement(
            table: 'orders',
        );

        try {
            $statement->compile();

            self::fail('Expected DatabaseException');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }

    public function testAllFoldsCompositeForeignKeyIntoSingleMetadata(): void
    {
        $rows = [
            [
                'constraint_name' => 'fk_orders_customer',
                'column_name' => 'customer_id',
                'referenced_table' => 'customers',
                'referenced_column' => 'id',
                'on_update' => 'CASCADE',
                'on_delete' => 'RESTRICT',
            ],
            [
                'constraint_name' => 'fk_orders_composite',
                'column_name' => 'tenant_id',
                'referenced_table' => 'tenants',
                'referenced_column' => 'id',
                'on_update' => 'NO ACTION',
                'on_delete' => 'CASCADE',
            ],
            [
                'constraint_name' => 'fk_orders_composite',
                'column_name' => 'ref_id',
                'referenced_table' => 'tenants',
                'referenced_column' => 'ref',
                'on_update' => 'NO ACTION',
                'on_delete' => 'CASCADE',
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new ListForeignKeysStatement(
            table: 'orders',
            connection: $connection,
        );

        $foreignKeys = $statement->all();

        self::assertCount(2, $foreignKeys);

        self::assertSame('fk_orders_customer', $foreignKeys[0]->name);
        self::assertSame(
            [
                'customer_id',
            ],
            $foreignKeys[0]->columns,
        );
        self::assertSame(
            [
                'id',
            ],
            $foreignKeys[0]->referencedColumns,
        );
        self::assertSame(ForeignKeyAction::CASCADE, $foreignKeys[0]->onUpdate);
        self::assertSame(ForeignKeyAction::RESTRICT, $foreignKeys[0]->onDelete);

        self::assertSame('fk_orders_composite', $foreignKeys[1]->name);
        self::assertSame(
            [
                'tenant_id',
                'ref_id',
            ],
            $foreignKeys[1]->columns,
        );
        self::assertSame(
            [
                'id',
                'ref',
            ],
            $foreignKeys[1]->referencedColumns,
        );
        self::assertSame(ForeignKeyAction::NO_ACTION, $foreignKeys[1]->onUpdate);
        self::assertSame(ForeignKeyAction::CASCADE, $foreignKeys[1]->onDelete);
    }

    public function testAllParsesSetNullAndSetDefaultActions(): void
    {
        $rows = [
            [
                'constraint_name' => 'fk1',
                'column_name' => 'a',
                'referenced_table' => 'x',
                'referenced_column' => 'id',
                'on_update' => 'SET NULL',
                'on_delete' => 'SET DEFAULT',
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new ListForeignKeysStatement(
            table: 'orders',
            connection: $connection,
        );

        $foreignKeys = $statement->all();

        self::assertSame(ForeignKeyAction::SET_NULL, $foreignKeys[0]->onUpdate);
        self::assertSame(ForeignKeyAction::SET_DEFAULT, $foreignKeys[0]->onDelete);
    }

    public function testAllParsesEmptyStringAsNoAction(): void
    {
        $rows = [
            [
                'constraint_name' => 'fk1',
                'column_name' => 'a',
                'referenced_table' => 'x',
                'referenced_column' => 'id',
                'on_update' => '',
                'on_delete' => '',
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new ListForeignKeysStatement(
            table: 'orders',
            connection: $connection,
        );

        $foreignKeys = $statement->all();

        self::assertSame(ForeignKeyAction::NO_ACTION, $foreignKeys[0]->onUpdate);
        self::assertSame(ForeignKeyAction::NO_ACTION, $foreignKeys[0]->onDelete);
    }

    public function testAllThrowsForUnmappedAction(): void
    {
        $rows = [
            [
                'constraint_name' => 'fk1',
                'column_name' => 'a',
                'referenced_table' => 'x',
                'referenced_column' => 'id',
                'on_update' => 'SOMETHING_WEIRD',
                'on_delete' => 'CASCADE',
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new ListForeignKeysStatement(
            table: 'orders',
            connection: $connection,
        );

        try {
            $statement->all();

            self::fail('Expected DatabaseException');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('SOMETHING_WEIRD', $exception->getMessage());
        }
    }

    public function testByNameReturnsKeyedMap(): void
    {
        $rows = [
            [
                'constraint_name' => 'fk_a',
                'column_name' => 'a',
                'referenced_table' => 'x',
                'referenced_column' => 'id',
                'on_update' => 'CASCADE',
                'on_delete' => 'CASCADE',
            ],
        ];

        $connection = new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                assocRows: $rows,
            ),
        );

        $statement = new ListForeignKeysStatement(
            table: 'orders',
            connection: $connection,
        );

        $byName = $statement->byName();

        self::assertArrayHasKey('fk_a', $byName);
        self::assertSame('fk_a', $byName['fk_a']->name);
    }
}
