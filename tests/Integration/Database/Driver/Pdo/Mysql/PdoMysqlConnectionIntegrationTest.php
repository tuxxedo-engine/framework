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

namespace Integration\Database\Driver\Pdo\Mysql;

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\MysqlSchemaProvider;
use Support\Database\MysqlTestEnv;
use Support\Database\PdoMysqlConnectionFactory;
use Support\Database\RealDatabaseIntegrationSetup;
use Support\Database\SchemaProvider;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Pdo\Mysql\Config\PdoMysqlConnectionConfig;
use Tuxxedo\Database\Driver\Pdo\Mysql\PdoMysqlConnection;

#[RequiresPhpExtension('pdo_mysql')]
class PdoMysqlConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    use RealDatabaseIntegrationSetup;

    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::mysqlUnavailableReason();
    }

    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        return PdoMysqlConnectionFactory::create(
            role: $role,
        );
    }

    protected function schemaProvider(): SchemaProvider
    {
        return new MysqlSchemaProvider();
    }

    public function testConfigWithExplicitDsnBypassesHostFieldConstruction(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $port = MysqlTestEnv::port();
        $dsn = \sprintf(
            'mysql:host=%s%s;dbname=%s;charset=%s',
            MysqlTestEnv::host(),
            $port !== null
                ? ';port=' . $port
                : '',
            MysqlTestEnv::databaseName(),
            MysqlTestEnv::charset(),
        );

        $connection = PdoMysqlConnection::create(
            container: $container,
            config: new PdoMysqlConnectionConfig(
                name: 'explicit-dsn',
                dsn: $dsn,
                username: MysqlTestEnv::username(),
                password: MysqlTestEnv::password(),
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

        $connection = PdoMysqlConnection::create(
            container: $container,
            config: new PdoMysqlConnectionConfig(
                name: 'with-timeout',
                host: MysqlTestEnv::host(),
                port: MysqlTestEnv::port(),
                username: MysqlTestEnv::username(),
                password: MysqlTestEnv::password(),
                database: MysqlTestEnv::databaseName(),
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

        $connection = PdoMysqlConnection::create(
            container: $container,
            config: new PdoMysqlConnectionConfig(
                name: 'eager',
                host: MysqlTestEnv::host(),
                port: MysqlTestEnv::port(),
                username: MysqlTestEnv::username(),
                password: MysqlTestEnv::password(),
                database: MysqlTestEnv::databaseName(),
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

        $connection = PdoMysqlConnection::create(
            container: $container,
            config: new PdoMysqlConnectionConfig(
                name: 'invalid',
                dsn: 'invalidscheme:test',
            ),
        );

        $this->expectException(DatabaseException::class);

        $connection->connect();
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
