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

use Tuxxedo\Database\SqlException;

abstract class AbstractInsertBulkBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    public function testInsertBulkSingleRow(): void
    {
        $this->createUsersSchema();

        $result = $this->connection->insertBulk(
            table: 'users',
        )
            ->values(
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                ],
            )
            ->execute();

        self::assertSame(
            1,
            $result->affectedRows,
        );
    }

    public function testInsertBulkMultipleRowsInSingleCall(): void
    {
        $this->createUsersSchema();

        $result = $this->connection->insertBulk(
            table: 'users',
        )
            ->values(
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                ],
                [
                    'name' => 'Bob',
                    'email' => 'bob@example.test',
                ],
                [
                    'name' => 'Charlie',
                    'email' => 'charlie@example.test',
                ],
            )
            ->execute();

        self::assertSame(
            3,
            $result->affectedRows,
        );
    }

    public function testInsertBulkStoresAllRows(): void
    {
        $this->createUsersSchema();

        $this->connection->insertBulk(
            table: 'users',
        )
            ->values(
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                ],
                [
                    'name' => 'Bob',
                    'email' => 'bob@example.test',
                ],
            )
            ->execute();

        $names = [];
        $rows = $this->connection->query(
            sql: 'SELECT name FROM users ORDER BY id',
            native: true,
        );

        foreach ($rows as $row) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Alice',
                'Bob',
            ],
            $names,
        );
    }

    public function testInsertBulkWithNullableValue(): void
    {
        $this->createUsersSchema();

        $this->connection->insertBulk(
            table: 'users',
        )
            ->values(
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                ],
                [
                    'name' => 'Charlie',
                    'email' => null,
                ],
            )
            ->execute();

        $row = $this->connection->query(
            sql: "SELECT email FROM users WHERE name = 'Charlie'",
            native: true,
        )->fetchAssoc();

        self::assertNull(
            $row['email'],
        );
    }

    public function testInsertBulkMultipleCallsAccumulate(): void
    {
        $this->createUsersSchema();

        $result = $this->connection->insertBulk(
            table: 'users',
        )
            ->values(
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                ],
            )
            ->values(
                [
                    'name' => 'Bob',
                    'email' => 'bob@example.test',
                ],
                [
                    'name' => 'Charlie',
                    'email' => 'charlie@example.test',
                ],
            )
            ->execute();

        self::assertSame(
            3,
            $result->affectedRows,
        );
    }

    public function testInsertBulkMismatchedRowSizeThrows(): void
    {
        $this->createUsersSchema();
        $this->expectException(SqlException::class);

        $this->connection->insertBulk(
            table: 'users',
        )
            ->values(
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                ],
                [
                    'name' => 'Bob',
                ],
            );
    }

    public function testInsertBulkMismatchedRowKeysThrows(): void
    {
        $this->createUsersSchema();
        $this->expectException(SqlException::class);

        $this->connection->insertBulk(
            table: 'users',
        )
            ->values(
                [
                    'name' => 'Alice',
                ],
            )
            ->values(
                [
                    'email' => 'bob@example.test',
                ],
            );
    }
}
