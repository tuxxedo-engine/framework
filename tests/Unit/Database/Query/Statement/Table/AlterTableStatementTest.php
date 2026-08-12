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
use Tuxxedo\Database\Query\Statement\Table\AlterTableStatement;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\Query\Statement\Table\ForeignKeyAction;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddIndex;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddPrimaryKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddUnique;
use Tuxxedo\Database\Query\Statement\Table\Operation\ChangeColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropIndex;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropPrimaryKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropUnique;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameTable;

class AlterTableStatementTest extends TestCase
{
    public function testEmptyOperationsList(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        self::assertSame(
            [],
            $statement->operations,
        );
    }

    public function testAddColumnAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $column = new IntegerColumn(
            name: 'quantity',
        );

        $result = $statement->addColumn(
            column: $column,
        );

        self::assertSame($statement, $result);
        self::assertCount(1, $statement->operations);

        $operation = $statement->operations[0];

        self::assertInstanceOf(AddColumn::class, $operation);
        self::assertSame($column, $operation->column);
    }

    public function testDropColumnAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->dropColumn(
            name: 'deprecated',
            ifExists: true,
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(DropColumn::class, $operation);
        self::assertSame('deprecated', $operation->name);
        self::assertTrue($operation->ifExists);
    }

    public function testDropColumnDefaultsIfExistsToFalse(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->dropColumn(
            name: 'deprecated',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(DropColumn::class, $operation);
        self::assertFalse($operation->ifExists);
    }

    public function testRenameColumnAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->renameColumn(
            from: 'name',
            to: 'title',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(RenameColumn::class, $operation);
        self::assertSame('name', $operation->from);
        self::assertSame('title', $operation->to);
    }

    public function testChangeColumnAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $column = new VarcharColumn(
            name: 'label',
            length: 128,
        );

        $statement->changeColumn(
            column: $column,
            using: 'label::text',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(ChangeColumn::class, $operation);
        self::assertSame($column, $operation->column);
        self::assertSame('label::text', $operation->using);
    }

    public function testChangeColumnDefaultsUsingToNull(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->changeColumn(
            column: new IntegerColumn(
                name: 'quantity',
            ),
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(ChangeColumn::class, $operation);
        self::assertNull($operation->using);
    }

    public function testRenameTableAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->renameTable(
            newName: 'gadgets',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(RenameTable::class, $operation);
        self::assertSame('gadgets', $operation->newName);
    }

    public function testAddIndexAppendsOperationWithExplicitName(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->addIndex(
            columns: [
                'category',
                'status',
            ],
            name: 'custom_idx',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(AddIndex::class, $operation);
        self::assertSame(
            [
                'category',
                'status',
            ],
            $operation->columns,
        );
        self::assertSame('custom_idx', $operation->name);
    }

    public function testAddIndexDefaultsNameToNull(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->addIndex(
            columns: [
                'category',
            ],
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(AddIndex::class, $operation);
        self::assertNull($operation->name);
    }

    public function testDropIndexAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->dropIndex(
            name: 'category_idx',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(DropIndex::class, $operation);
        self::assertSame('category_idx', $operation->name);
    }

    public function testAddUniqueAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->addUnique(
            columns: [
                'sku',
            ],
            name: 'sku_unq',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(AddUnique::class, $operation);
        self::assertSame(
            [
                'sku',
            ],
            $operation->columns,
        );
        self::assertSame('sku_unq', $operation->name);
    }

    public function testDropUniqueAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->dropUnique(
            name: 'sku_unq',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(DropUnique::class, $operation);
        self::assertSame('sku_unq', $operation->name);
    }

    public function testAddForeignKeyAppendsOperationWithAllOptions(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->addForeignKey(
            columns: [
                'owner_id',
            ],
            referencedTable: 'users',
            referencedColumns: [
                'id',
            ],
            onDelete: ForeignKeyAction::CASCADE,
            onUpdate: ForeignKeyAction::RESTRICT,
            name: 'widgets_owner_fk',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(AddForeignKey::class, $operation);
        self::assertSame(
            [
                'owner_id',
            ],
            $operation->columns,
        );
        self::assertSame('users', $operation->referencedTable);
        self::assertSame(
            [
                'id',
            ],
            $operation->referencedColumns,
        );
        self::assertSame(ForeignKeyAction::CASCADE, $operation->onDelete);
        self::assertSame(ForeignKeyAction::RESTRICT, $operation->onUpdate);
        self::assertSame('widgets_owner_fk', $operation->name);
    }

    public function testAddForeignKeyDefaultsAreNull(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->addForeignKey(
            columns: [
                'owner_id',
            ],
            referencedTable: 'users',
            referencedColumns: [
                'id',
            ],
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(AddForeignKey::class, $operation);
        self::assertNull($operation->onDelete);
        self::assertNull($operation->onUpdate);
        self::assertNull($operation->name);
    }

    public function testDropForeignKeyAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->dropForeignKey(
            name: 'widgets_owner_fk',
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(DropForeignKey::class, $operation);
        self::assertSame('widgets_owner_fk', $operation->name);
    }

    public function testAddPrimaryKeyAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->addPrimaryKey(
            columns: [
                'tenant_id',
                'id',
            ],
        );

        $operation = $statement->operations[0];

        self::assertInstanceOf(AddPrimaryKey::class, $operation);
        self::assertSame(
            [
                'tenant_id',
                'id',
            ],
            $operation->columns,
        );
    }

    public function testDropPrimaryKeyAppendsOperation(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->dropPrimaryKey();

        $operation = $statement->operations[0];

        self::assertInstanceOf(DropPrimaryKey::class, $operation);
    }

    public function testFluentChainPreservesOrder(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement
            ->addColumn(
                column: new IntegerColumn(
                    name: 'quantity',
                ),
            )
            ->dropColumn(
                name: 'legacy',
            )
            ->renameTable(
                newName: 'gadgets',
            );

        self::assertCount(3, $statement->operations);
        self::assertInstanceOf(AddColumn::class, $statement->operations[0]);
        self::assertInstanceOf(DropColumn::class, $statement->operations[1]);
        self::assertInstanceOf(RenameTable::class, $statement->operations[2]);
    }

    public function testGenerateStatementsDelegatesToDialect(): void
    {
        $dialect = new StubDialect();
        $dialect->compileAlterTableResult = [
            'ALTER TABLE "widgets" DROP COLUMN "legacy"',
        ];

        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->dropColumn(
            name: 'legacy',
        );

        $result = $statement->generateStatements(
            dialect: $dialect,
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" DROP COLUMN "legacy"',
            ],
            $result,
        );
        self::assertSame('widgets', $dialect->compileAlterTableTable);
        self::assertSame($statement->operations, $dialect->compileAlterTableOperations);
    }

    public function testCompileReturnsSemicolonJoinedSql(): void
    {
        $dialect = new StubDialect();
        $dialect->compileAlterTableResult = [
            'ALTER TABLE "widgets" ADD COLUMN "quantity" INTEGER',
            'ALTER TABLE "widgets" DROP COLUMN "legacy"',
        ];

        $connection = new StubConnection(
            dialectImpl: $dialect,
        );

        $statement = new AlterTableStatement(
            table: 'widgets',
            connection: $connection,
        );

        $statement
            ->addColumn(
                column: new IntegerColumn(
                    name: 'quantity',
                ),
            )
            ->dropColumn(
                name: 'legacy',
            );

        $compiled = $statement->compile();

        self::assertSame(
            "ALTER TABLE \"widgets\" ADD COLUMN \"quantity\" INTEGER;\nALTER TABLE \"widgets\" DROP COLUMN \"legacy\"",
            $compiled->sql,
        );
    }

    public function testCompileUsesExplicitConnectionArgumentWhenProvided(): void
    {
        $dialect = new StubDialect();
        $dialect->compileAlterTableResult = [
            'ALTER TABLE "widgets" DROP COLUMN "legacy"',
        ];

        $connection = new StubConnection(
            dialectImpl: $dialect,
        );

        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        $statement->dropColumn(
            name: 'legacy',
        );

        $compiled = $statement->compile(
            connection: $connection,
        );

        self::assertSame(
            'ALTER TABLE "widgets" DROP COLUMN "legacy"',
            $compiled->sql,
        );
    }

    public function testCompileThrowsWithoutConnection(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        try {
            $statement->compile();

            self::fail('Expected DatabaseException to be thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }

    public function testExecuteQueriesConnectionOncePerStatement(): void
    {
        $dialect = new StubDialect();
        $dialect->compileAlterTableResult = [
            'ALTER TABLE "widgets" ADD COLUMN "quantity" INTEGER',
            'ALTER TABLE "widgets" DROP COLUMN "legacy"',
        ];

        $connection = new StubConnection(
            dialectImpl: $dialect,
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(),
        );

        $statement = new AlterTableStatement(
            table: 'widgets',
            connection: $connection,
        );

        $statement
            ->addColumn(
                column: new IntegerColumn(
                    name: 'quantity',
                ),
            )
            ->dropColumn(
                name: 'legacy',
            );

        $result = $statement->execute();

        self::assertInstanceOf(StubResultSet::class, $result);
        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD COLUMN "quantity" INTEGER',
                'ALTER TABLE "widgets" DROP COLUMN "legacy"',
            ],
            $connection->recordedQueries,
        );
    }

    public function testExecuteWithNoStatementsRunsFallbackQuery(): void
    {
        $dialect = new StubDialect();

        $connection = new StubConnection(
            dialectImpl: $dialect,
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(),
        );

        $statement = new AlterTableStatement(
            table: 'widgets',
            connection: $connection,
        );

        $result = $statement->execute();

        self::assertInstanceOf(StubResultSet::class, $result);
        self::assertSame(
            [
                'SELECT 1',
            ],
            $connection->recordedQueries,
        );
    }

    public function testExecuteThrowsWithoutConnection(): void
    {
        $statement = new AlterTableStatement(
            table: 'widgets',
        );

        try {
            $statement->execute();

            self::fail('Expected DatabaseException to be thrown');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('connection', \strtolower($exception->getMessage()));
        }
    }
}
