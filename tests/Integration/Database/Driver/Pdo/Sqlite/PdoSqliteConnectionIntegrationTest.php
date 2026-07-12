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

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Pdo\Sqlite\Config\PdoSqliteConnectionConfig;
use Tuxxedo\Database\Driver\Pdo\Sqlite\PdoSqliteConnection;

#[RequiresPhpExtension('pdo_sqlite')]
class PdoSqliteConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return PdoSqliteConnection::create(
            container: $container,
            config: new PdoSqliteConnectionConfig(
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

    public function testConfigWithExplicitDsnBypassesDatabaseFieldConstruction(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PdoSqliteConnection::create(
            container: $container,
            config: new PdoSqliteConnectionConfig(
                name: 'explicit-dsn',
                dsn: 'sqlite::memory:',
            ),
        );

        $connection->connect();

        self::assertTrue(
            $connection->isConnected(),
        );

        $connection->close();
    }

    public function testConfigWithTimeoutPassesPdoAttrTimeoutOption(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PdoSqliteConnection::create(
            container: $container,
            config: new PdoSqliteConnectionConfig(
                name: 'with-timeout',
                database: ':memory:',
                timeout: 5,
            ),
        );

        $connection->connect();

        self::assertTrue(
            $connection->isConnected(),
        );

        $connection->close();
    }

    public function testConfigWithLazyFalseConnectsEagerly(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PdoSqliteConnection::create(
            container: $container,
            config: new PdoSqliteConnectionConfig(
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

    public function testConnectingWithInvalidDsnThrowsDatabaseException(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PdoSqliteConnection::create(
            container: $container,
            config: new PdoSqliteConnectionConfig(
                name: 'invalid',
                dsn: 'invalidscheme:test',
            ),
        );

        $this->expectException(DatabaseException::class);

        $connection->connect();
    }

    public function testQueryWithMalformedSqlThrowsDatabaseException(): void
    {
        $this->expectException(DatabaseException::class);

        $this->connection->query(
            sql: 'THIS IS NOT VALID SQL',
            native: true,
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

    public function testNonNativeQueryRunsThroughStatementParserAndBindsPositionally(): void
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

        $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Charlie')",
            native: true,
        );

        $result = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users WHERE name = :name',
            parameters: [
                'name' => 'Alice',
            ],
        );

        $row = $result->fetchAssoc();

        self::assertEquals(
            1,
            $row['c'],
        );
    }

    public function testNonNativeQueryWithArrayPlaceholderRoundTrips(): void
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

        $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Charlie')",
            native: true,
        );

        $result = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users WHERE name IN (:names[])',
            parameters: [
                'names' => [
                    'Alice',
                    'Charlie',
                ],
            ],
        );

        $row = $result->fetchAssoc();

        self::assertEquals(
            2,
            $row['c'],
        );
    }
}
