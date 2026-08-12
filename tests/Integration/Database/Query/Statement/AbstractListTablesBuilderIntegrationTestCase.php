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

abstract class AbstractListTablesBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    public function testListTablesIncludesCreatedTable(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->execute();

        $tables = $this->connection->listTables()->all();

        self::assertContains(
            'widgets',
            $tables,
        );
    }

    public function testListTablesExcludesDroppedTable(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->execute();

        $this->connection->dropTable(
            table: 'widgets',
        )->execute();

        $tables = $this->connection->listTables()->all();

        self::assertNotContains(
            'widgets',
            $tables,
        );
    }
}
