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

namespace Integration\Database\Driver\Sqlite;

use Integration\Database\AbstractResultSetIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Sqlite\Config\SqliteConnectionConfig;
use Tuxxedo\Database\Driver\Sqlite\SqliteConnection;

#[RequiresPhpExtension('sqlite3')]
class SqliteResultSetIntegrationTest extends AbstractResultSetIntegrationTestCase
{
    protected function createConnection(): ConnectionInterface
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return SqliteConnection::create(
            container: $container,
            config: new SqliteConnectionConfig(
                name: 'test',
                database: ':memory:',
            ),
        );
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
}
