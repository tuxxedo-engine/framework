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

use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;

abstract class AbstractAlterTableBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    public function testAlterTableViaBuilderAddsColumn(): void
    {
        $table = $this->connection->createTable(
            table: 'widgets',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->execute();

        $this->connection->alterTable(
            table: 'widgets',
        )
            ->addColumn(
                column: new IntegerColumn(
                    name: 'quantity',
                    nullable: true,
                ),
            )
            ->execute();

        $this->connection->insert(
            table: 'widgets',
        )
            ->set(
                column: 'id',
                value: 1,
            )
            ->set(
                column: 'quantity',
                value: 42,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT quantity FROM widgets WHERE id = 1',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            42,
            $row['quantity'],
        );
    }
}
