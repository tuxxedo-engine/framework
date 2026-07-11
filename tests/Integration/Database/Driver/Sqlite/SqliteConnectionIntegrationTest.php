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
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Sqlite\Config\SqliteConnectionConfig;
use Tuxxedo\Database\Driver\Sqlite\SqliteConnection;

#[RequiresPhpExtension('sqlite3')]
class SqliteConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return SqliteConnection::create(
            container: $container,
            config: new SqliteConnectionConfig(
                name: 'test',
                role: $role,
                database: ':memory:',
            ),
        );
    }

    protected function createUsersSchema(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT)',
            native: true,
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

    public function testQueryWithMalformedSqlThrowsDatabaseException(): void
    {
        $this->expectException(DatabaseException::class);

        $this->connection->query(
            sql: 'THIS IS NOT VALID SQL',
            native: true,
        );
    }

    public function testNativeQuerySkipsArrayParameter(): void
    {
        $this->createUsersSchema();

        $result = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            parameters: [
                ':unused' => [
                    1,
                    2,
                    3,
                ],
            ],
            native: true,
        );

        $row = $result->fetchAssoc();

        self::assertEquals(
            0,
            $row['c'],
        );
    }

    public function testExecuteConstraintViolationThrowsDatabaseException(): void
    {
        $this->createUsersSchema();

        $this->expectException(DatabaseException::class);

        $this->connection->query(
            sql: 'INSERT INTO users (id, name) VALUES (1, NULL)',
            native: true,
        );
    }
}
