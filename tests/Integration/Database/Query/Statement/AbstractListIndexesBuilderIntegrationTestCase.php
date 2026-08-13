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

use Tuxxedo\Database\Query\Statement\Table\IndexMetadataInterface;

abstract class AbstractListIndexesBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    protected function buildWidgetsTable(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->varchar(
            name: 'email',
            length: 190,
        );

        $table->varchar(
            name: 'name',
            length: 190,
        );

        $table->unique('email');
        $table->index('name', 'email');

        $table->execute();
    }

    public function testListIndexesReportsUniqueIndex(): void
    {
        $this->buildWidgetsTable();

        $indexes = $this->connection->listIndexes(
            table: 'widgets',
        )->all();

        $unique = $this->findByColumns(
            indexes: $indexes,
            columns: [
                'email',
            ],
        );

        self::assertNotNull($unique, 'unique index on email should exist');
        self::assertTrue($unique->unique);
        self::assertFalse($unique->primary);
    }

    public function testListIndexesReportsCompositeIndex(): void
    {
        $this->buildWidgetsTable();

        $indexes = $this->connection->listIndexes(
            table: 'widgets',
        )->all();

        $composite = $this->findByColumns(
            indexes: $indexes,
            columns: [
                'name',
                'email',
            ],
        );

        self::assertNotNull($composite, 'composite index on (name, email) should exist');
        self::assertFalse($composite->unique);
        self::assertFalse($composite->primary);
    }

    public function testByNameReturnsMapKeyedByIndexName(): void
    {
        $this->buildWidgetsTable();

        $byName = $this->connection->listIndexes(
            table: 'widgets',
        )->byName();

        self::assertNotEmpty($byName);

        foreach ($byName as $name => $index) {
            self::assertSame($name, $index->name);
        }
    }

    public function testListIndexesReturnsEmptyForTableWithoutNamedIndexes(): void
    {
        $table = $this->connection->createTable(
            table: 'plain_widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->execute();

        $indexes = $this->connection->listIndexes(
            table: 'plain_widgets',
        )->all();

        $unexpected = $this->findByColumns(
            indexes: $indexes,
            columns: [
                'email',
            ],
        );

        self::assertNull($unexpected);
    }

    /**
     * @param list<IndexMetadataInterface> $indexes
     * @param list<string> $columns
     */
    private function findByColumns(
        array $indexes,
        array $columns,
    ): ?IndexMetadataInterface {
        foreach ($indexes as $index) {
            if ($index->columns === $columns) {
                return $index;
            }
        }

        return null;
    }
}
