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

use Tuxxedo\Database\DatabaseException;

abstract class AbstractDropTableBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    private function createWidgetsTable(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE widgets (id INTEGER PRIMARY KEY)',
            native: true,
        );
    }

    public function testDropTableRemovesExistingTable(): void
    {
        $this->createWidgetsTable();

        $this->connection->dropTable(
            table: 'widgets',
        )->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->query(
            sql: 'SELECT COUNT(*) FROM widgets',
            native: true,
        );
    }

    public function testDropTableWithoutIfExistsThrowsOnMissingTable(): void
    {
        $this->expectException(DatabaseException::class);

        $this->connection->dropTable(
            table: 'nonexistent',
        )->execute();
    }

    public function testDropTableWithIfExistsSucceedsOnMissingTable(): void
    {
        $this->connection->dropTable(
            table: 'nonexistent',
        )
            ->ifExists()
            ->execute();

        self::assertTrue(
            $this->connection->isConnected(),
        );
    }

    public function testDropTableWithIfExistsRemovesExistingTable(): void
    {
        $this->createWidgetsTable();

        $this->connection->dropTable(
            table: 'widgets',
        )
            ->ifExists()
            ->execute();

        $this->expectException(DatabaseException::class);

        $this->connection->query(
            sql: 'SELECT COUNT(*) FROM widgets',
            native: true,
        );
    }
}
