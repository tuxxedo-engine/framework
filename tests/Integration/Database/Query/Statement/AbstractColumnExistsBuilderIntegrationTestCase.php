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

abstract class AbstractColumnExistsBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    private function createWidgetsTable(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->text(
            name: 'label',
        );

        $table->execute();
    }

    public function testColumnExistsReturnsTrueForExistingColumn(): void
    {
        $this->createWidgetsTable();

        self::assertTrue(
            $this->connection->columnExists(
                table: 'widgets',
                column: 'label',
            )->exists(),
        );
    }

    public function testColumnExistsReturnsFalseForMissingColumn(): void
    {
        $this->createWidgetsTable();

        self::assertFalse(
            $this->connection->columnExists(
                table: 'widgets',
                column: 'phantom',
            )->exists(),
        );
    }

    public function testColumnExistsReturnsFalseForMissingTable(): void
    {
        self::assertFalse(
            $this->connection->columnExists(
                table: 'phantom',
                column: 'anything',
            )->exists(),
        );
    }
}
