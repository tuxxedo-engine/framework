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

abstract class AbstractTableExistsBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    public function testTableExistsReturnsTrueForExistingTable(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->execute();

        self::assertTrue(
            $this->connection->tableExists(
                table: 'widgets',
            )->exists(),
        );
    }

    public function testTableExistsReturnsFalseForMissingTable(): void
    {
        self::assertFalse(
            $this->connection->tableExists(
                table: 'phantom',
            )->exists(),
        );
    }

    public function testTableExistsReturnsFalseAfterDropTable(): void
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

        self::assertFalse(
            $this->connection->tableExists(
                table: 'widgets',
            )->exists(),
        );
    }
}
