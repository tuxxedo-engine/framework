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

namespace Integration\Database\Query\Statement;

use Fixture\Database\Status;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Statement\Table\ForeignKeyAction;
use Tuxxedo\Database\SqlException;

abstract class AbstractCreateTableBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    public function testCreateTableWithBigIntegerColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->bigInteger(
            name: 'value',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'value',
                value: 9000000000,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT value FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            9000000000,
            $row['value'],
        );
    }

    public function testCreateTableWithBlobColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->blob(
            name: 'payload',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'payload',
                value: 'binary-data',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT payload FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            'binary-data',
            $row['payload'],
        );
    }

    public function testCreateTableWithBooleanColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->boolean(
            name: 'flag',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'flag',
                value: true,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT flag FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            1,
            $row['flag'],
        );
    }

    public function testCreateTableWithCharColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->char(
            name: 'code',
            length: 3,
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'code',
                value: 'ABC',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT code FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'ABC',
            $row['code'],
        );
    }

    public function testCreateTableWithDateColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->date(
            name: 'day',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'day',
                value: '2026-07-13',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT day FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            '2026-07-13',
            $row['day'],
        );
    }

    public function testCreateTableWithDateTimeColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->dateTime(
            name: 'at',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'at',
                value: '2026-07-13 12:34:56',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT at FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            '2026-07-13 12:34:56',
            $row['at'],
        );
    }

    public function testCreateTableWithDecimalColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->decimal(
            name: 'price',
            precision: 10,
            scale: 2,
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'price',
                value: 19.99,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT price FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            19.99,
            $row['price'],
        );
    }

    public function testCreateTableWithDoubleColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->double(
            name: 'ratio',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'ratio',
                value: 3.14,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT ratio FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEqualsWithDelta(
            3.14,
            $row['ratio'],
            0.0001,
        );
    }

    public function testCreateTableWithEnumerationFromArray(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->enumeration(
            name: 'status',
            values: [
                'active',
                'inactive',
            ],
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'status',
                value: 'active',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT status FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'active',
            $row['status'],
        );
    }

    public function testCreateTableWithEnumerationFromEnumClass(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->enumeration(
            name: 'status',
            values: Status::class,
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'status',
                value: Status::ACTIVE->value,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT status FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'active',
            $row['status'],
        );
    }

    public function testCreateTableWithIntegerColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'value',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'value',
                value: 42,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT value FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            42,
            $row['value'],
        );
    }

    public function testCreateTableWithJsonColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->json(
            name: 'payload',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'payload',
                value: '{"key":"value"}',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT payload FROM widgets',
            native: true,
        )->fetchAssoc();

        /** @var string $payload */
        $payload = $row['payload'];

        self::assertSame(
            [
                'key' => 'value',
            ],
            \json_decode($payload, associative: true),
        );
    }

    public function testCreateTableWithSmallIntegerColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->smallInteger(
            name: 'value',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'value',
                value: 100,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT value FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            100,
            $row['value'],
        );
    }

    public function testCreateTableWithTextColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->text(
            name: 'body',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'body',
                value: 'long text content',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT body FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'long text content',
            $row['body'],
        );
    }

    public function testCreateTableWithTimeColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->time(
            name: 'clock',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'clock',
                value: '12:34:56',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT clock FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            '12:34:56',
            $row['clock'],
        );
    }

    public function testCreateTableWithTimestampColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->timestamp(
            name: 'stamped',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'stamped',
                value: '2026-07-13 12:34:56',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT stamped FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            '2026-07-13 12:34:56',
            $row['stamped'],
        );
    }

    public function testCreateTableWithTinyIntegerColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->tinyInteger(
            name: 'value',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'value',
                value: 5,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT value FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            5,
            $row['value'],
        );
    }

    public function testCreateTableWithVarcharColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->varchar(
            name: 'label',
            length: 50,
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'label',
                value: 'my-label',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT label FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'my-label',
            $row['label'],
        );
    }

    public function testCreateTableWithRawColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->raw(
            name: 'label',
            sql: 'TEXT',
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'label',
                value: 'raw-value',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT label FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'raw-value',
            $row['label'],
        );
    }

    public function testCreateTableWithNullableColumnAllowsNullInsert(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->varchar(
            name: 'label',
            length: 50,
            nullable: true,
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'label',
                value: null,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT label FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertNull(
            $row['label'],
        );
    }

    public function testCreateTableWithNotNullColumnRejectsNullInsert(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->varchar(
            name: 'label',
            length: 50,
        );

        $table->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->query(
            sql: 'INSERT INTO widgets (label) VALUES (NULL)',
            native: true,
        );
    }

    public function testCreateTableWithPrimaryKeyEnforcesUniqueness(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'id',
                value: 1,
            )
            ->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'id',
                value: 1,
            )
            ->execute();
    }

    public function testCreateTableWithUniqueConstraintEnforcesUniqueness(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->varchar(
            name: 'code',
            length: 20,
            unique: true,
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'id',
                value: 1,
            )
            ->set(
                column: 'code',
                value: 'X',
            )
            ->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'id',
                value: 2,
            )
            ->set(
                column: 'code',
                value: 'X',
            )
            ->execute();
    }

    public function testCreateTableWithAutoIncrementAssignsIds(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            autoIncrement: true,
        );

        $table->varchar(
            name: 'label',
            length: 20,
        );

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'label',
                value: 'first',
            )
            ->execute();

        $id = $this->connection->lastInsertIdAsInt();

        self::assertNotNull(
            $id,
        );

        self::assertGreaterThan(
            0,
            $id,
        );
    }

    public function testCreateTableWithBoolDefault(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->boolean(
            name: 'flag',
            default: true,
        );

        $table->execute();

        $this->connection->query(
            sql: 'INSERT INTO widgets (id) VALUES (1)',
            native: true,
        );

        $row = $this->connection->query(
            sql: 'SELECT flag FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            1,
            $row['flag'],
        );
    }

    public function testCreateTableWithIntDefault(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->integer(
            name: 'value',
            default: 42,
        );

        $table->execute();

        $this->connection->query(
            sql: 'INSERT INTO widgets (id) VALUES (1)',
            native: true,
        );

        $row = $this->connection->query(
            sql: 'SELECT value FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            42,
            $row['value'],
        );
    }

    public function testCreateTableWithStringDefault(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->varchar(
            name: 'label',
            length: 50,
            default: 'hello',
        );

        $table->execute();

        $this->connection->query(
            sql: 'INSERT INTO widgets (id) VALUES (1)',
            native: true,
        );

        $row = $this->connection->query(
            sql: 'SELECT label FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'hello',
            $row['label'],
        );
    }

    public function testCreateTableWithStringDefaultEscapesSingleQuote(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->varchar(
            name: 'label',
            length: 50,
            default: "O'Brien",
        );

        $table->execute();

        $this->connection->query(
            sql: 'INSERT INTO widgets (id) VALUES (1)',
            native: true,
        );

        $row = $this->connection->query(
            sql: 'SELECT label FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            "O'Brien",
            $row['label'],
        );
    }

    public function testCreateTableIfNotExistsAcceptsExistingTable(): void
    {
        $first = $this->connection->createTable(
            table: 'widgets',
        )
            ->ifNotExists();

        $first->integer(
            name: 'id',
            primaryKey: true,
        );

        $first->execute();

        $second = $this->connection->createTable(
            table: 'widgets',
        )
            ->ifNotExists();

        $second->integer(
            name: 'id',
            primaryKey: true,
        );

        $second->execute();

        $count = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM widgets',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            0,
            $count['c'],
        );
    }

    public function testCreateTableWithCompositePrimaryKey(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->varchar(
            name: 'scope',
            length: 32,
        );

        $table->varchar(
            name: 'name',
            length: 32,
        );

        $table->primaryKey('scope', 'name');

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'scope',
                value: 'a',
            )
            ->set(
                column: 'name',
                value: 'x',
            )
            ->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'scope',
                value: 'a',
            )
            ->set(
                column: 'name',
                value: 'x',
            )
            ->execute();
    }

    public function testCreateTableWithMultiColumnUniqueConstraintEnforcesUniqueness(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
            autoIncrement: true,
        );

        $table->integer(
            name: 'a',
        );

        $table->integer(
            name: 'b',
        );

        $table->unique('a', 'b');

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'a',
                value: 1,
            )
            ->set(
                column: 'b',
                value: 2,
            )
            ->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'a',
                value: 1,
            )
            ->set(
                column: 'b',
                value: 3,
            )
            ->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'a',
                value: 1,
            )
            ->set(
                column: 'b',
                value: 2,
            )
            ->execute();
    }

    public function testCreateTableWithIndexCreatesLookupIndex(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
            autoIncrement: true,
        );

        $table->varchar(
            name: 'label',
            length: 32,
        );

        $table->index('label');

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'label',
                value: 'alpha',
            )
            ->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'label',
                value: 'beta',
            )
            ->execute();

        $row = $this->connection->query(
            sql: "SELECT label FROM widgets WHERE label = 'alpha'",
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'alpha',
            $row['label'],
        );
    }

    public function testCreateTableWithMultiColumnIndexCreatesLookupIndex(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
            autoIncrement: true,
        );

        $table->varchar(
            name: 'category',
            length: 32,
        );

        $table->varchar(
            name: 'label',
            length: 32,
        );

        $table->index('category', 'label');

        $table->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'category',
                value: 'x',
            )
            ->set(
                column: 'label',
                value: 'alpha',
            )
            ->execute();

        $row = $this->connection->query(
            sql: "SELECT category, label FROM widgets WHERE category = 'x' AND label = 'alpha'",
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'x',
            $row['category'],
        );
    }

    public function testCreateTableWithForeignKeyAcceptsValidReference(): void
    {
        $parent = $this->connection->createTable(
            table: 'parents',
        );

        $parent->integer(
            name: 'id',
            primaryKey: true,
            autoIncrement: true,
        );

        $parent->varchar(
            name: 'name',
            length: 32,
        );

        $parent->execute();

        $child = $this->connection->createTable(
            table: 'children',
        );

        $child->integer(
            name: 'id',
            primaryKey: true,
            autoIncrement: true,
        );

        $child->integer(
            name: 'parent_id',
        );

        $child->foreignKey(
            columns: [
                'parent_id',
            ],
            referencedTable: 'parents',
            referencedColumns: [
                'id',
            ],
            onDelete: ForeignKeyAction::CASCADE,
        );

        $child->execute();

        $this->connection->insert(
            table: 'parents',
        )
            ->set(
                column: 'name',
                value: 'root',
            )
            ->execute();

        $parentId = $this->connection->lastInsertIdAsInt();

        self::assertNotNull($parentId);

        $this->connection->insert(
            table: 'children',
        )
            ->set(
                column: 'parent_id',
                value: $parentId,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT parent_id FROM children',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            $parentId,
            $row['parent_id'],
        );
    }

    public function testCreateTableWithForeignKeyOnUpdateAction(): void
    {
        $parent = $this->connection->createTable(
            table: 'parents',
        );

        $parent->integer(
            name: 'id',
            primaryKey: true,
            autoIncrement: true,
        );

        $parent->varchar(
            name: 'name',
            length: 32,
        );

        $parent->execute();

        $child = $this->connection->createTable(
            table: 'children',
        );

        $child->integer(
            name: 'id',
            primaryKey: true,
            autoIncrement: true,
        );

        $child->integer(
            name: 'parent_id',
        );

        $child->foreignKey(
            columns: [
                'parent_id',
            ],
            referencedTable: 'parents',
            referencedColumns: [
                'id',
            ],
            onUpdate: ForeignKeyAction::CASCADE,
        );

        $child->execute();

        $this->connection->insert(
            table: 'parents',
        )
            ->set(
                column: 'name',
                value: 'root',
            )
            ->execute();

        $parentId = $this->connection->lastInsertIdAsInt();

        self::assertNotNull($parentId);

        $this->connection->insert(
            table: 'children',
        )
            ->set(
                column: 'parent_id',
                value: $parentId,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT parent_id FROM children',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            $parentId,
            $row['parent_id'],
        );
    }

    public function testCreateTableWithoutColumnsThrows(): void
    {
        $this->expectException(SqlException::class);

        $this->connection->createTable(
            table: 'widgets',
        )->execute();
    }
}
