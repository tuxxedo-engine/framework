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

abstract class AbstractInsertBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    private function createTypesSchema(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider()->typesSchemaSql(),
            native: true,
        );
    }

    public function testInsertSetsRowAndReturnsAffectedRowsOne(): void
    {
        $this->createUsersSchema();

        $result = $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        self::assertSame(
            1,
            $result->affectedRows,
        );
    }

    public function testChainedSetInsertsAllColumns(): void
    {
        $this->createUsersSchema();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
            )
            ->set(
                column: 'email',
                value: 'alice@example.test',
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT name, email FROM users',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'Alice',
            $row['name'],
        );

        self::assertSame(
            'alice@example.test',
            $row['email'],
        );
    }

    public function testInsertNullValueIsStored(): void
    {
        $this->createUsersSchema();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Charlie',
            )
            ->set(
                column: 'email',
                value: null,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT email FROM users',
            native: true,
        )->fetchAssoc();

        self::assertNull(
            $row['email'],
        );
    }

    public function testInsertIntValueRoundTrips(): void
    {
        $this->createTypesSchema();

        $this->connection->insert(
            table: 'types',
        )
            ->set(
                column: 'num',
                value: 42,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT num FROM types',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            42,
            $row['num'],
        );
    }

    public function testInsertFloatValueRoundTrips(): void
    {
        $this->createTypesSchema();

        $this->connection->insert(
            table: 'types',
        )
            ->set(
                column: 'ratio',
                value: 3.5,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT ratio FROM types',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            3.5,
            $row['ratio'],
        );
    }

    public function testInsertBoolValueRoundTrips(): void
    {
        $this->createTypesSchema();

        $this->connection->insert(
            table: 'types',
        )
            ->set(
                column: 'flag',
                value: true,
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT flag FROM types',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            1,
            $row['flag'],
        );
    }

    public function testInsertStringValueRoundTripsWithSingleQuote(): void
    {
        $this->createUsersSchema();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: "O'Brien",
            )
            ->execute();

        $row = $this->connection->query(
            sql: 'SELECT name FROM users',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            "O'Brien",
            $row['name'],
        );
    }

    public function testLastInsertIdReturnsAssignedId(): void
    {
        $this->createUsersSchema();

        $this->connection->insert(
            table: 'users',
        )
            ->set(
                column: 'name',
                value: 'Alice',
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
}
