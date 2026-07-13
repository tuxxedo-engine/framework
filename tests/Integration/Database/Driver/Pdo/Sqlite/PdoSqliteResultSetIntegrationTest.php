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

namespace Integration\Database\Driver\Pdo\Sqlite;

use Integration\Database\AbstractResultSetIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\PdoSqliteConnectionFactory;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[RequiresPhpExtension('pdo_sqlite')]
class PdoSqliteResultSetIntegrationTest extends AbstractResultSetIntegrationTestCase
{
    protected function createConnection(): ConnectionInterface
    {
        return PdoSqliteConnectionFactory::create();
    }

    protected function createUsersSchemaWithSampleRows(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT)',
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Bob', 'bob@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Charlie', NULL)",
            native: true,
        );
    }

    public function testFetchAssocOnDmlResultSetThrowsFromEmptyResultSet(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Dave')",
            native: true,
        );

        $this->expectException(DatabaseException::class);

        $result->fetchAssoc();
    }

    public function testFetchRowOnDmlResultSetThrowsFromEmptyResultSet(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Dave')",
            native: true,
        );

        $this->expectException(DatabaseException::class);

        $result->fetchRow();
    }

    public function testFetchObjectOnDmlResultSetThrowsFromEmptyResultSet(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Dave')",
            native: true,
        );

        $this->expectException(DatabaseException::class);

        $result->fetchObject();
    }

    public function testCountOnDmlResultSetReturnsZero(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Dave')",
            native: true,
        );

        self::assertCount(
            0,
            $result,
        );
    }
}
