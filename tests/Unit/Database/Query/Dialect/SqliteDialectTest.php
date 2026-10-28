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
use Tuxxedo\Database\Query\Dialect\Sqlite\SqliteDialect;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddFullTextIndex;
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
use Tuxxedo\Database\SqlException;

class SqliteDialectTest extends TestCase
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
            (new SqliteDialect())->interpretBoolean($value),
        );
    }

    #[DataProvider('interpretBooleanFalseDataProvider')]
    public function testInterpretBooleanReturnsFalse(
        mixed $value,
    ): void {
        self::assertFalse(
            (new SqliteDialect())->interpretBoolean($value),
        );
    }

    public function testConditionOperatorRendersOperatorsVerbatim(): void
    {
        $dialect = new SqliteDialect();

        foreach (ConditionOperator::cases() as $operator) {
            self::assertSame($operator->value, $dialect->conditionOperator($operator));
        }
    }

    public function testFullTextConditionIsUnsupported(): void
    {
        try {
            (new SqliteDialect())->fullTextCondition(
                columns: [
                    'search_text',
                ],
                terms: [
                    'mint',
                ],
            );

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('does not support full-text search', $exception->getMessage());
        }
    }

    public function testCreateFullTextIndexIsUnsupported(): void
    {
        try {
            (new SqliteDialect())->createFullTextIndex(
                table: 'articles',
                name: 'articles_search_text_ft',
                columns: [
                    'search_text',
                ],
            );

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('does not support full-text search', $exception->getMessage());
        }
    }

    public function testAlterTableAddFullTextIndexIsUnsupported(): void
    {
        $this->expectException(SqlException::class);

        (new SqliteDialect())->alterTable(
            table: 'articles',
            operations: [
                new AddFullTextIndex(
                    columns: [
                        'search_text',
                    ],
                ),
            ],
        );
    }

    public function testIndexExistsBuildsCatalogQuery(): void
    {
        $result = (new SqliteDialect())->indexExists(
            table: 'articles',
            name: 'articles_search_text_ft',
        );

        self::assertSame(
            'SELECT EXISTS(SELECT 1 FROM sqlite_master WHERE type = \'index\' AND tbl_name = :table AND name = :name)',
            $result->sql,
        );

        self::assertSame(
            [
                'table' => 'articles',
                'name' => 'articles_search_text_ft',
            ],
            $result->parameters,
        );
    }

    public function testCompileAlterTableReturnsEmptyForNoOperations(): void
    {
        self::assertSame(
            [],
            (new SqliteDialect())->alterTable(
                table: 'widgets',
                operations: [],
            ),
        );
    }

    public function testCompileAlterTableAddColumn(): void
    {
        $sql = (new SqliteDialect())->alterTable(
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
        $sql = (new SqliteDialect())->alterTable(
            table: 'widgets',
            operations: [
                new DropColumn(
                    name: 'legacy',
                ),
            ],
        );

        self::assertSame(
            [
                'ALTER TABLE "widgets" DROP COLUMN "legacy"',
            ],
            self::sqlOf($sql),
        );
    }

    public function testCompileAlterTableRenameColumn(): void
    {
        $sql = (new SqliteDialect())->alterTable(
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

    public function testCompileAlterTableRenameTable(): void
    {
        $sql = (new SqliteDialect())->alterTable(
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

    public function testCompileAlterTableChangeColumnThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
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

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('ChangeColumn', $exception->getMessage());
        }
    }

    public function testCompileAlterTableAddIndexThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
                table: 'widgets',
                operations: [
                    new AddIndex(
                        columns: [
                            'category',
                        ],
                    ),
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('AddIndex', $exception->getMessage());
        }
    }

    public function testCompileAlterTableDropIndexThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
                table: 'widgets',
                operations: [
                    new DropIndex(
                        name: 'category_idx',
                    ),
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('DropIndex', $exception->getMessage());
        }
    }

    public function testCompileAlterTableAddUniqueThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
                table: 'widgets',
                operations: [
                    new AddUnique(
                        columns: [
                            'sku',
                        ],
                    ),
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('AddUnique', $exception->getMessage());
        }
    }

    public function testCompileAlterTableDropUniqueThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
                table: 'widgets',
                operations: [
                    new DropUnique(
                        name: 'sku_unq',
                    ),
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('DropUnique', $exception->getMessage());
        }
    }

    public function testCompileAlterTableAddForeignKeyThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
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

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('AddForeignKey', $exception->getMessage());
        }
    }

    public function testCompileAlterTableDropForeignKeyThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
                table: 'widgets',
                operations: [
                    new DropForeignKey(
                        name: 'widgets_owner_fk',
                    ),
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('DropForeignKey', $exception->getMessage());
        }
    }

    public function testCompileAlterTableAddPrimaryKeyThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
                table: 'widgets',
                operations: [
                    new AddPrimaryKey(
                        columns: [
                            'id',
                        ],
                    ),
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('AddPrimaryKey', $exception->getMessage());
        }
    }

    public function testCompileAlterTableDropPrimaryKeyThrows(): void
    {
        try {
            (new SqliteDialect())->alterTable(
                table: 'widgets',
                operations: [
                    new DropPrimaryKey(),
                ],
            );

            self::fail('Expected SqlException to be thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString('DropPrimaryKey', $exception->getMessage());
        }
    }

    public function testTableExistsBuildsSqliteMasterQuery(): void
    {
        $result = (new SqliteDialect())->tableExists(
            table: 'widgets',
        );

        self::assertSame(
            'SELECT EXISTS(SELECT 1 FROM sqlite_master WHERE type = \'table\' AND name = :table)',
            $result->sql,
        );

        self::assertSame(
            [
                'table' => 'widgets',
            ],
            $result->parameters,
        );
    }

    public function testColumnExistsBuildsPragmaTableInfoQuery(): void
    {
        $result = (new SqliteDialect())->columnExists(
            table: 'widgets',
            column: 'quantity',
        );

        self::assertSame(
            'SELECT EXISTS(SELECT 1 FROM pragma_table_info(:table) WHERE name = :column)',
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

    public function testListDatabasesBuildsPragmaDatabaseListQuery(): void
    {
        $result = (new SqliteDialect())->listDatabases();

        self::assertSame(
            'SELECT name FROM pragma_database_list ORDER BY seq',
            $result->sql,
        );

        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testListTablesBuildsSqliteMasterQuery(): void
    {
        $result = (new SqliteDialect())->listTables();

        self::assertSame(
            'SELECT name FROM sqlite_master WHERE type = \'table\' AND name NOT LIKE \'sqlite_%\' ORDER BY name',
            $result->sql,
        );

        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testListIndexesBuildsPragmaJoin(): void
    {
        $result = (new SqliteDialect())->listIndexes(
            table: 'widgets',
        );

        self::assertStringContainsString('pragma_index_list(:table)', $result->sql);
        self::assertStringContainsString('pragma_index_info(il.name)', $result->sql);
        self::assertStringContainsString('is_unique', $result->sql);
        self::assertStringContainsString('is_primary', $result->sql);
        self::assertSame(
            [
                'table' => 'widgets',
            ],
            $result->parameters,
        );
    }

    public function testListForeignKeysBuildsPragmaForeignKeyList(): void
    {
        $result = (new SqliteDialect())->listForeignKeys(
            table: 'widgets',
        );

        self::assertStringContainsString('pragma_foreign_key_list(:table)', $result->sql);
        self::assertStringContainsString('constraint_name', $result->sql);
        self::assertStringContainsString('on_update', $result->sql);
        self::assertStringContainsString('on_delete', $result->sql);
        self::assertSame(
            [
                'table' => 'widgets',
            ],
            $result->parameters,
        );
    }

    public function testDescribeTableBuildsPragmaTableInfoQuery(): void
    {
        $result = (new SqliteDialect())->describeTable(
            table: 'widgets',
        );

        self::assertStringContainsString('pragma_table_info(:table)', $result->sql);
        self::assertStringContainsString('sqlite_master', $result->sql);
        self::assertStringContainsString('AUTOINCREMENT', $result->sql);
        self::assertStringContainsString('is_primary', $result->sql);
        self::assertStringContainsString('is_auto_increment', $result->sql);
        self::assertSame(
            [
                'table' => 'widgets',
            ],
            $result->parameters,
        );
    }
}
