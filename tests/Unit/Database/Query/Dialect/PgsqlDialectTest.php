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

namespace Unit\Database\Query\Dialect;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\PgsqlDialect;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\BooleanColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\Query\Statement\Table\ForeignKeyAction;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddIndex;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddPrimaryKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddUnique;
use Tuxxedo\Database\Query\Statement\Table\Operation\AlterOperationInterface;
use Tuxxedo\Database\Query\Statement\Table\Operation\ChangeColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropIndex;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropPrimaryKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropUnique;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameTable;
use Tuxxedo\Database\SqlException;

class PgsqlDialectTest extends TestCase
{
    /**
     * @param list<StatementParserResultInterface> $results
     * @return list<string>
     */
    private static function sqlOf(
        array $results,
    ): array {
        $sql = [];

        foreach ($results as $result) {
            $sql[] = $result->sql;
        }

        return $sql;
    }

    /**
     * @return \Generator<array{0: mixed}>
     */
    public static function interpretBooleanTrueDataProvider(): \Generator
    {
        yield [
            't',
        ];

        yield [
            '1',
        ];

        yield [
            1,
        ];

        yield [
            true,
        ];
    }

    /**
     * @return \Generator<array{0: mixed}>
     */
    public static function interpretBooleanFalseDataProvider(): \Generator
    {
        yield [
            'f',
        ];

        yield [
            '0',
        ];

        yield [
            'anything',
        ];

        yield [
            0,
        ];

        yield [
            false,
        ];

        yield [
            null,
        ];
    }

    #[DataProvider('interpretBooleanTrueDataProvider')]
    public function testInterpretBooleanReturnsTrue(
        mixed $value,
    ): void {
        self::assertTrue(
            (new PgsqlDialect())->interpretBoolean($value),
        );
    }

    #[DataProvider('interpretBooleanFalseDataProvider')]
    public function testInterpretBooleanReturnsFalse(
        mixed $value,
    ): void {
        self::assertFalse(
            (new PgsqlDialect())->interpretBoolean($value),
        );
    }

    public function testCompileAlterTableReturnsEmptyForNoOperations(): void
    {
        self::assertSame(
            [],
            (new PgsqlDialect())->alterTable(
                table: 'widgets',
                operations: [],
            ),
        );
    }

    public function testCompileAlterTableAddColumn(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddColumn(
                    column: new IntegerColumn(
                        name: 'quantity',
                    ),
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD COLUMN "quantity" INTEGER NOT NULL',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropColumn(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropColumn(
                    name: 'legacy',
                    ifExists: true,
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" DROP COLUMN IF EXISTS "legacy"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableRenameColumn(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new RenameColumn(
                    from: 'name',
                    to: 'title',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" RENAME COLUMN "name" TO "title"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumnDecomposesToThreeStatements(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new ChangeColumn(
                    column: new VarcharColumn(
                        name: 'label',
                        length: 128,
                    ),
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ALTER COLUMN "label" TYPE VARCHAR(128)',
                'ALTER TABLE "widgets" ALTER COLUMN "label" SET NOT NULL',
                'ALTER TABLE "widgets" ALTER COLUMN "label" DROP DEFAULT',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumnNullableSetsDropNotNull(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new ChangeColumn(
                    column: new VarcharColumn(
                        name: 'label',
                        length: 128,
                        nullable: true,
                    ),
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ALTER COLUMN "label" TYPE VARCHAR(128)',
                'ALTER TABLE "widgets" ALTER COLUMN "label" DROP NOT NULL',
                'ALTER TABLE "widgets" ALTER COLUMN "label" DROP DEFAULT',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumnWithDefault(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new ChangeColumn(
                    column: new IntegerColumn(
                        name: 'quantity',
                        default: 0,
                    ),
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ALTER COLUMN "quantity" TYPE INTEGER',
                'ALTER TABLE "widgets" ALTER COLUMN "quantity" SET NOT NULL',
                'ALTER TABLE "widgets" ALTER COLUMN "quantity" SET DEFAULT 0',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumnWithUsingExpression(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new ChangeColumn(
                    column: new IntegerColumn(
                        name: 'quantity',
                    ),
                    using: 'quantity::integer',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ALTER COLUMN "quantity" TYPE INTEGER USING quantity::integer',
                'ALTER TABLE "widgets" ALTER COLUMN "quantity" SET NOT NULL',
                'ALTER TABLE "widgets" ALTER COLUMN "quantity" DROP DEFAULT',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableRenameTable(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new RenameTable(
                    newName: 'gadgets',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" RENAME TO "gadgets"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddIndexGeneratesDefaultName(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddIndex(
                    columns: [
                        'category',
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                'CREATE INDEX "widgets_category_idx" ON "widgets" ("category")',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropIndex(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropIndex(
                    name: 'category_idx',
                ),
            ],
        );

        self::assertSame(
            [
                'DROP INDEX "category_idx"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddUnique(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddUnique(
                    columns: [
                        'sku',
                    ],
                    name: 'sku_unq',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD CONSTRAINT "sku_unq" UNIQUE ("sku")',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropUnique(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropUnique(
                    name: 'sku_unq',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" DROP CONSTRAINT "sku_unq"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddForeignKey(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddForeignKey(
                    columns: [
                        'owner_id',
                    ],
                    referencedTable: 'users',
                    referencedColumns: [
                        'id',
                    ],
                    onDelete: ForeignKeyAction::CASCADE,
                    name: 'widgets_owner_fk',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD CONSTRAINT "widgets_owner_fk" FOREIGN KEY ("owner_id") REFERENCES "users" ("id") ON DELETE CASCADE',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropForeignKey(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropForeignKey(
                    name: 'widgets_owner_fk',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" DROP CONSTRAINT "widgets_owner_fk"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddPrimaryKey(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddPrimaryKey(
                    columns: [
                        'tenant_id',
                        'id',
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD PRIMARY KEY ("tenant_id", "id")',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropPrimaryKey(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropPrimaryKey(),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" DROP CONSTRAINT "widgets_pkey"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableEmitsOneStatementPerOperation(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddColumn(
                    column: new IntegerColumn(
                        name: 'quantity',
                    ),
                ),
                new DropColumn(
                    name: 'legacy',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD COLUMN "quantity" INTEGER NOT NULL',
                'ALTER TABLE "widgets" DROP COLUMN "legacy"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddForeignKeyEmitsOnUpdate(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddForeignKey(
                    columns: [
                        'owner_id',
                    ],
                    referencedTable: 'users',
                    referencedColumns: [
                        'id',
                    ],
                    onUpdate: ForeignKeyAction::RESTRICT,
                    name: 'widgets_owner_fk',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD CONSTRAINT "widgets_owner_fk" FOREIGN KEY ("owner_id") REFERENCES "users" ("id") ON UPDATE RESTRICT',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddUniqueGeneratesDefaultName(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddUnique(
                    columns: [
                        'sku',
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD CONSTRAINT "widgets_sku_unq" UNIQUE ("sku")',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddForeignKeyGeneratesDefaultName(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddForeignKey(
                    columns: [
                        'owner_id',
                    ],
                    referencedTable: 'users',
                    referencedColumns: [
                        'id',
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" ADD CONSTRAINT "widgets_owner_id_fk" FOREIGN KEY ("owner_id") REFERENCES "users" ("id")',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumnRendersBooleanTrueDefault(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new ChangeColumn(
                    column: new BooleanColumn(
                        name: 'active',
                        default: true,
                    ),
                ),
            ],
        );

        self::assertContains(
            'ALTER TABLE "widgets" ALTER COLUMN "active" SET DEFAULT TRUE',
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumnRendersBooleanFalseDefault(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new ChangeColumn(
                    column: new BooleanColumn(
                        name: 'active',
                        default: false,
                    ),
                ),
            ],
        );

        self::assertContains(
            'ALTER TABLE "widgets" ALTER COLUMN "active" SET DEFAULT FALSE',
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumnRendersStringDefaultEscaped(): void
    {
        $sql = (new PgsqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new ChangeColumn(
                    column: new VarcharColumn(
                        name: 'label',
                        length: 128,
                        default: "hello'world",
                    ),
                ),
            ],
        );

        self::assertContains(
            'ALTER TABLE "widgets" ALTER COLUMN "label" SET DEFAULT \'hello\'\'world\'',
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumnRejectsNonAbstractColumn(): void
    {
        $column = new class () implements ColumnInterface {
            public string $name {
                get {
                    return 'label';
                }
            }

            public function toSql(
                DialectInterface $dialect,
            ): string {
                return '"label" TEXT';
            }
        };

        try {
            (new PgsqlDialect())->alterTable(
                table: 'widgets',
                operations: [
                    new ChangeColumn(
                        column: $column,
                    ),
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('ChangeColumn', $exception->getMessage());
        }
    }

    public function testCompileAlterTableUnknownOperationThrows(): void
    {
        $operation = new class () implements AlterOperationInterface {
        };

        try {
            (new PgsqlDialect())->alterTable(
                table: 'widgets',
                operations: [
                    $operation,
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('PgsqlDialect', $exception->getMessage());
        }
    }

    public function testTableExistsBuildsInformationSchemaQuery(): void
    {
        $result = (new PgsqlDialect())->tableExists(
            table: 'widgets',
        );

        self::assertSame(
            'SELECT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :table)',
            $result->sql,
        );
        self::assertSame(
            [
                'table' => 'widgets',
            ],
            $result->parameters,
        );
    }

    public function testColumnExistsBuildsInformationSchemaQuery(): void
    {
        $result = (new PgsqlDialect())->columnExists(
            table: 'widgets',
            column: 'quantity',
        );

        self::assertSame(
            'SELECT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = :table AND column_name = :column)',
            $result->sql,
        );
        self::assertSame(
            [
                'table' => 'widgets',
                'column' => 'quantity',
            ],
            $result->parameters,
        );
    }

    public function testListDatabasesBuildsPgDatabaseQuery(): void
    {
        $result = (new PgsqlDialect())->listDatabases();

        self::assertSame(
            'SELECT datname FROM pg_database WHERE NOT datistemplate ORDER BY datname',
            $result->sql,
        );
        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testListTablesBuildsPgTablesQuery(): void
    {
        $result = (new PgsqlDialect())->listTables();

        self::assertSame(
            'SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = current_schema() ORDER BY tablename',
            $result->sql,
        );
        self::assertSame(
            [],
            $result->parameters,
        );
    }
}
