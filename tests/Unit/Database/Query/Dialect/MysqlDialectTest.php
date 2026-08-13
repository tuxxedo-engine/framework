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
use Tuxxedo\Database\Query\Dialect\MysqlDialect;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
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

class MysqlDialectTest extends TestCase
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
            (new MysqlDialect())->interpretBoolean($value),
        );
    }

    #[DataProvider('interpretBooleanFalseDataProvider')]
    public function testInterpretBooleanReturnsFalse(
        mixed $value,
    ): void {
        self::assertFalse(
            (new MysqlDialect())->interpretBoolean($value),
        );
    }

    public function testCompileAlterTableReturnsEmptyForNoOperations(): void
    {
        self::assertSame(
            [],
            (new MysqlDialect())->alterTable(
                table: 'widgets',
                operations: [],
            ),
        );
    }

    public function testCompileAlterTableAddColumn(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` ADD COLUMN `quantity` INTEGER NOT NULL',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropColumn(): void
    {
        $sql = (new MysqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropColumn(
                    name: 'legacy',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE `widgets` DROP COLUMN `legacy`',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropColumnIfExists(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` DROP COLUMN IF EXISTS `legacy`',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableRenameColumn(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` RENAME COLUMN `name` TO `title`',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableChangeColumn(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` MODIFY COLUMN `label` VARCHAR(128) NOT NULL',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableRenameTable(): void
    {
        $sql = (new MysqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new RenameTable(
                    newName: 'gadgets',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE `widgets` RENAME TO `gadgets`',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddIndexWithExplicitName(): void
    {
        $sql = (new MysqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddIndex(
                    columns: [
                        'category',
                        'status',
                    ],
                    name: 'custom_idx',
                ),
            ],
        );

        self::assertSame(
            [
                'CREATE INDEX `custom_idx` ON `widgets` (`category`, `status`)',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddIndexGeneratesDefaultName(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'CREATE INDEX `widgets_category_idx` ON `widgets` (`category`)',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropIndex(): void
    {
        $sql = (new MysqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropIndex(
                    name: 'category_idx',
                ),
            ],
        );

        self::assertSame(
            [
                'DROP INDEX `category_idx` ON `widgets`',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddUnique(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` ADD CONSTRAINT `sku_unq` UNIQUE (`sku`)',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropUnique(): void
    {
        $sql = (new MysqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropUnique(
                    name: 'sku_unq',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE `widgets` DROP INDEX `sku_unq`',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddForeignKey(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                    onUpdate: ForeignKeyAction::RESTRICT,
                    name: 'widgets_owner_fk',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE `widgets` ADD CONSTRAINT `widgets_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropForeignKey(): void
    {
        $sql = (new MysqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropForeignKey(
                    name: 'widgets_owner_fk',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE `widgets` DROP FOREIGN KEY `widgets_owner_fk`',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddPrimaryKey(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` ADD PRIMARY KEY (`tenant_id`, `id`)',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableDropPrimaryKey(): void
    {
        $sql = (new MysqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropPrimaryKey(),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE `widgets` DROP PRIMARY KEY',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableMergesStructuralClauses(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` ADD COLUMN `quantity` INTEGER NOT NULL, DROP COLUMN `legacy`',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableEmitsIndexOperationsSeparately(): void
    {
        $sql = (new MysqlDialect())->alterTable(
            table: 'widgets',
            operations: [
                new AddColumn(
                    column: new IntegerColumn(
                        name: 'quantity',
                    ),
                ),
                new AddIndex(
                    columns: [
                        'quantity',
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE `widgets` ADD COLUMN `quantity` INTEGER NOT NULL',
                'CREATE INDEX `widgets_quantity_idx` ON `widgets` (`quantity`)',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddUniqueGeneratesDefaultName(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` ADD CONSTRAINT `widgets_sku_unq` UNIQUE (`sku`)',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableAddForeignKeyGeneratesDefaultName(): void
    {
        $sql = (new MysqlDialect())->alterTable(
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
                'ALTER TABLE `widgets` ADD CONSTRAINT `widgets_owner_id_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableUnknownOperationThrows(): void
    {
        $operation = new class () implements AlterOperationInterface {
        };

        try {
            (new MysqlDialect())->alterTable(
                table: 'widgets',
                operations: [
                    $operation,
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('MysqlDialect', $exception->getMessage());
        }
    }

    public function testTableExistsBuildsInformationSchemaQuery(): void
    {
        $result = (new MysqlDialect())->tableExists(
            table: 'widgets',
        );

        self::assertSame(
            'SELECT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table)',
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
        $result = (new MysqlDialect())->columnExists(
            table: 'widgets',
            column: 'quantity',
        );

        self::assertSame(
            'SELECT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column)',
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

    public function testListDatabasesBuildsInformationSchemaQuery(): void
    {
        $result = (new MysqlDialect())->listDatabases();

        self::assertSame(
            'SELECT schema_name FROM information_schema.schemata ORDER BY schema_name',
            $result->sql,
        );
        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testListTablesBuildsInformationSchemaQuery(): void
    {
        $result = (new MysqlDialect())->listTables();

        self::assertSame(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name',
            $result->sql,
        );
        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testListIndexesBuildsInformationSchemaQuery(): void
    {
        $result = (new MysqlDialect())->listIndexes(
            table: 'widgets',
        );

        self::assertStringContainsString('information_schema.statistics', $result->sql);
        self::assertStringContainsString(':table', $result->sql);
        self::assertStringContainsString('index_name', $result->sql);
        self::assertStringContainsString('is_unique', $result->sql);
        self::assertStringContainsString('is_primary', $result->sql);
        self::assertSame(
            [
                'table' => 'widgets',
            ],
            $result->parameters,
        );
    }

    public function testListForeignKeysBuildsInformationSchemaQuery(): void
    {
        $result = (new MysqlDialect())->listForeignKeys(
            table: 'widgets',
        );

        self::assertStringContainsString('key_column_usage', $result->sql);
        self::assertStringContainsString('referential_constraints', $result->sql);
        self::assertStringContainsString(':table', $result->sql);
        self::assertStringContainsString('constraint_name', $result->sql);
        self::assertStringContainsString('referenced_table', $result->sql);
        self::assertStringContainsString('on_update', $result->sql);
        self::assertStringContainsString('on_delete', $result->sql);
        self::assertSame(
            [
                'table' => 'widgets',
            ],
            $result->parameters,
        );
    }

    public function testDescribeTableBuildsInformationSchemaQuery(): void
    {
        $result = (new MysqlDialect())->describeTable(
            table: 'widgets',
        );

        self::assertStringContainsString('information_schema.columns', $result->sql);
        self::assertStringContainsString(':table', $result->sql);
        self::assertStringContainsString('column_type', $result->sql);
        self::assertStringContainsString('is_nullable', $result->sql);
        self::assertStringContainsString('column_default', $result->sql);
        self::assertStringContainsString('column_key', $result->sql);
        self::assertStringContainsString('auto_increment', $result->sql);
        self::assertSame(
            [
                'table' => 'widgets',
            ],
            $result->parameters,
        );
    }
}
