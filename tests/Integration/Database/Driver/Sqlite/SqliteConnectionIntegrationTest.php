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

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\SqliteConnectionFactory;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Sqlite\Config\SqliteConnectionConfig;
use Tuxxedo\Database\Driver\Sqlite\SqliteConnection;

#[RequiresPhpExtension('sqlite3')]
class SqliteConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        return SqliteConnectionFactory::create(
            role: $role,
        );
    }

    public function testConfigWithLazyFalseConnectsEagerly(): void
    {
        $connection = SqliteConnection::create(
            container: new Container(),
            config: new SqliteConnectionConfig(
                name: 'eager',
                database: ':memory:',
                lazy: false,
            ),
        );

        self::assertTrue(
            $connection->isConnected(),
        );

        $connection->close();
    }

    public function testNativeQueryBindsScalarNamedParameter(): void
    {
        $this->createUsersSchema();

        $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Alice')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Bob')",
            native: true,
        );

        $result = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users WHERE name = :name',
            parameters: [
                ':name' => 'Alice',
            ],
            native: true,
        );

        $row = $result->fetchAssoc();

        self::assertEquals(
            1,
            $row['c'],
        );
    }
}
