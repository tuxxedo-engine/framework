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

use Tuxxedo\Database\Query\Statement\UpdateStatement;

abstract class AbstractUpdateBuilderIntegrationTestCase extends AbstractWhereClauseBuilderIntegrationTestCase
{
    protected function runWhereMatch(
        \Closure $configureBuilder,
    ): bool {
        $builder = $this->connection->update(
            table: 'users',
        )
            ->set(
                column: 'email',
                value: 'sentinel@example.test',
            );

        $configureBuilder($builder);

        /** @var UpdateStatement $builder */
        return $builder->execute()->affectedRows > 0;
    }

    private function createCountersSchema(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider()->countersSchemaSql(),
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO counters (num, ratio) VALUES (10, 1.5)',
            native: true,
        );
    }

    public function testUpdateSingleColumnAffectsOneRow(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->update(
            table: 'users',
        )
            ->set(
                column: 'email',
                value: 'updated@example.test',
            )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        self::assertSame(
            1,
            $result->affectedRows,
        );
    }

    public function testUpdateChainedSetUpdatesMultipleColumns(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $this->connection->update(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Renamed',
            )
            ->set(
                column: 'email',
                value: 'renamed@example.test',
            )
            ->where(
                column: 'id',
                value: 1,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT name, email FROM users WHERE id = 1',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'Renamed',
            $row['name'],
        );

        self::assertSame(
            'renamed@example.test',
            $row['email'],
        );
    }

    public function testUpdateNullValueIsStored(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $this->connection->update(
            table: 'users',
        )
            ->set(
                column: 'email',
                value: null,
            )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        $row = $this->connection->query(
            sql: "SELECT email FROM users WHERE name = 'Alice'",
            native: true,
        )->fetchAssoc();

        self::assertNull(
            $row['email'],
        );
    }

    public function testUpdateActuallyChangesData(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $this->connection->update(
            table: 'users',
        )
            ->set(
                column: 'email',
                value: 'newalice@example.test',
            )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        $row = $this->connection->query(
            sql: "SELECT email FROM users WHERE name = 'Alice'",
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'newalice@example.test',
            $row['email'],
        );
    }

    public function testUpdateNoMatchReturnsZeroAffectedRows(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->update(
            table: 'users',
        )
            ->set(
                column: 'email',
                value: 'nobody@example.test',
            )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->execute();

        self::assertSame(
            0,
            $result->affectedRows,
        );
    }

    public function testUpdateIncrementByInt(): void
    {
        $this->createCountersSchema();

        $this->connection->update(
            table: 'counters',
        )
            ->increment(
                column: 'num',
            )
            ->where(
                column: 'id',
                value: 1,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT num FROM counters WHERE id = 1',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            11,
            $row['num'],
        );
    }

    public function testUpdateIncrementByFloat(): void
    {
        $this->createCountersSchema();

        $this->connection->update(
            table: 'counters',
        )
            ->increment(
                column: 'ratio',
                amount: 2.5,
            )
            ->where(
                column: 'id',
                value: 1,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT ratio FROM counters WHERE id = 1',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            4.0,
            $row['ratio'],
        );
    }

    public function testUpdateDecrementByInt(): void
    {
        $this->createCountersSchema();

        $this->connection->update(
            table: 'counters',
        )
            ->decrement(
                column: 'num',
                amount: 3,
            )
            ->where(
                column: 'id',
                value: 1,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT num FROM counters WHERE id = 1',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            7,
            $row['num'],
        );
    }
}
