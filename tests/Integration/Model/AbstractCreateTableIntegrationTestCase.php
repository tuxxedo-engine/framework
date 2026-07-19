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

namespace Integration\Model;

use Fixture\Model\AllColumnTypes;
use Fixture\Model\ClassLevelIndex;
use Fixture\Model\ClassLevelUnique;
use Fixture\Model\Country;
use Fixture\Model\PostStatus;
use Fixture\Model\Setting;
use Fixture\Model\Tag;
use Fixture\Model\User;
use Tuxxedo\Database\DatabaseException;

abstract class AbstractCreateTableIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    public function testCreateTableForSimpleModelBuildsWorkingTable(): void
    {
        $this->modelsManager
            ->createTable(Country::class)
            ->execute();

        $this->connection->insert(
            table: 'countries',
        )
            ->set(column: 'name', value: 'Sweden')
            ->set(column: 'code', value: 'SE')
            ->execute();

        $row = $this->connection->select(
            table: 'countries',
        )
            ->select('name', 'code')
            ->execute()
            ->fetchAssoc();

        self::assertSame('Sweden', $row['name']);
        self::assertSame('SE', $row['code']);
    }

    public function testCreateTableForCompositeKeyModelEnforcesUniqueness(): void
    {
        $this->modelsManager
            ->createTable(Setting::class)
            ->execute();

        $this->connection->insert(
            table: 'settings',
        )
            ->set(column: 'scope', value: 'ui')
            ->set(column: 'name', value: 'theme')
            ->set(column: 'value', value: 'dark')
            ->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->insert(
            table: 'settings',
        )
            ->set(column: 'scope', value: 'ui')
            ->set(column: 'name', value: 'theme')
            ->set(column: 'value', value: 'light')
            ->execute();
    }

    public function testCreateTableWithClassLevelUniqueEnforcesCompositeConstraint(): void
    {
        $this->modelsManager
            ->createTable(ClassLevelUnique::class)
            ->execute();

        $this->connection->insert(
            table: 'class_level_unique',
        )
            ->set(column: 'tenant_id', value: 1)
            ->set(column: 'slug', value: 'x')
            ->set(column: 'external_ref', value: 'a')
            ->execute();

        $this->connection->insert(
            table: 'class_level_unique',
        )
            ->set(column: 'tenant_id', value: 2)
            ->set(column: 'slug', value: 'x')
            ->set(column: 'external_ref', value: 'b')
            ->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->insert(
            table: 'class_level_unique',
        )
            ->set(column: 'tenant_id', value: 1)
            ->set(column: 'slug', value: 'x')
            ->set(column: 'external_ref', value: 'c')
            ->execute();
    }

    public function testCreateTableWithClassLevelIndexAcceptsLookupInsert(): void
    {
        $this->modelsManager
            ->createTable(ClassLevelIndex::class)
            ->execute();

        $this->connection->insert(
            table: 'class_level_index',
        )
            ->set(column: 'status', value: 'active')
            ->set(column: 'created_at', value: '2026-07-13 00:00:00')
            ->execute();

        $row = $this->connection->select(
            table: 'class_level_index',
        )
            ->select('status')
            ->where(column: 'status', value: 'active')
            ->execute()
            ->fetchAssoc();

        self::assertSame('active', $row['status']);
    }

    public function testCreateTableWithIdentifierPromotesColumnToUnique(): void
    {
        $this->modelsManager
            ->createTable(Tag::class)
            ->execute();

        $this->connection->insert(
            table: 'tags',
        )
            ->set(column: 'slug', value: 'php')
            ->set(column: 'name', value: 'PHP')
            ->set(column: 'category', value: 'lng')
            ->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->insert(
            table: 'tags',
        )
            ->set(column: 'slug', value: 'php')
            ->set(column: 'name', value: 'PHP Again')
            ->set(column: 'category', value: 'lng')
            ->execute();
    }

    public function testCreateTableForBelongsToEmitsForeignKey(): void
    {
        $this->modelsManager
            ->createTable(Country::class)
            ->execute();

        $this->modelsManager
            ->createTable(User::class)
            ->execute();

        $this->connection->insert(
            table: 'countries',
        )
            ->set(column: 'name', value: 'Sweden')
            ->set(column: 'code', value: 'SE')
            ->execute();

        $countryId = $this->connection->lastInsertIdAsInt();

        self::assertNotNull($countryId);

        $this->connection->insert(
            table: 'users',
        )
            ->set(column: 'name', value: 'Alice')
            ->set(column: 'email', value: 'alice@example.test')
            ->set(column: 'isActive', value: true)
            ->set(column: 'postCount', value: 0)
            ->set(column: 'score', value: 0.0)
            ->set(column: 'country_id', value: $countryId)
            ->execute();

        $row = $this->connection->select(
            table: 'users',
        )
            ->select('name', 'country_id')
            ->execute()
            ->fetchAssoc();

        self::assertSame('Alice', $row['name']);
        self::assertEquals($countryId, $row['country_id']);
    }

    public function testCreateTableForAllColumnTypesBuildsWorkingTable(): void
    {
        $this->modelsManager
            ->createTable(AllColumnTypes::class)
            ->execute();

        $this->connection->insert(
            table: 'all_column_types',
        )
            ->set(column: 'tiny_value', value: 1)
            ->set(column: 'small_value', value: 2)
            ->set(column: 'int_value', value: 3)
            ->set(column: 'big_value', value: 9000000000)
            ->set(column: 'flag', value: true)
            ->set(column: 'ratio', value: 1.5)
            ->set(column: 'price', value: '9.99')
            ->set(column: 'code', value: 'ABC')
            ->set(column: 'label', value: 'hello')
            ->set(column: 'body', value: 'long text')
            ->set(column: 'payload', value: 'binary-data')
            ->set(column: 'meta', value: '{"k":"v"}')
            ->set(column: 'status', value: PostStatus::PUBLISHED->value)
            ->execute();

        $row = $this->connection->select(
            table: 'all_column_types',
        )
            ->select('tiny_value', 'big_value', 'flag', 'code', 'status')
            ->execute()
            ->fetchAssoc();

        self::assertEquals(1, $row['tiny_value']);
        self::assertEquals(9000000000, $row['big_value']);
        self::assertEquals(1, $row['flag']);
        self::assertSame('ABC', $row['code']);
        self::assertSame('published', $row['status']);
    }
}
