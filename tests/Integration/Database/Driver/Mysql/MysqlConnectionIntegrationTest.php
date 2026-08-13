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

namespace Integration\Database\Driver\Mysql;

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\MysqlConnectionFactory;
use Support\Database\MysqlSchemaProvider;
use Support\Database\MysqlTestEnv;
use Support\Database\RealDatabaseIntegrationSetup;
use Support\Database\SchemaProvider;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Mysql\Config\MysqlConnectionConfig;
use Tuxxedo\Database\Driver\Mysql\MysqlConnection;

#[RequiresPhpExtension('mysqli')]
class MysqlConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    use RealDatabaseIntegrationSetup;

    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::mysqlUnavailableReason();
    }

    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        return MysqlConnectionFactory::create(
            role: $role,
        );
    }

    protected function schemaProvider(): SchemaProvider
    {
        return new MysqlSchemaProvider();
    }

    public function testConfigWithLazyFalseConnectsEagerly(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = MysqlConnection::create(
            container: $container,
            config: new MysqlConnectionConfig(
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

    public function testConfigWithTimeoutSetsMysqliOptions(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = MysqlConnection::create(
            container: $container,
            config: new MysqlConnectionConfig(
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

    public function testConfigWithPersistentPrependsPPrefixToHost(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = MysqlConnection::create(
            container: $container,
            config: new MysqlConnectionConfig(
                name: 'persistent',
                host: MysqlTestEnv::host(),
                port: MysqlTestEnv::port(),
                username: MysqlTestEnv::username(),
                password: MysqlTestEnv::password(),
                database: MysqlTestEnv::databaseName(),
                persistent: true,
            ),
        );

        $connection->connect();

        self::assertTrue(
            $connection->isConnected(),
        );

        $connection->close();
    }

    public function testPingReturnsFalseWhenConnectCheckFails(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = MysqlConnection::create(
            container: $container,
            config: new MysqlConnectionConfig(
                name: 'unreachable',
                host: '127.0.0.1',
                port: 1,
                username: 'nobody',
                password: 'nopass',
                database: 'nowhere',
                timeout: 1,
            ),
        );

        self::assertFalse(
            $connection->ping(),
        );
    }

    public function testSwitchDatabaseUpdatesCurrentDatabase(): void
    {
        $original = $this->connection->currentDatabase();

        $this->connection->switchDatabase('information_schema');

        self::assertSame(
            'information_schema',
            $this->connection->currentDatabase(),
        );

        $this->connection->switchDatabase($original);
    }

    public function testSwitchDatabaseThrowsWhileInTransaction(): void
    {
        $this->connection->begin();

        try {
            $this->expectException(DatabaseException::class);

            $this->connection->switchDatabase('information_schema');
        } finally {
            $this->connection->rollback();
        }
    }

    public function testSwitchDatabaseThrowsOnNonexistentDatabase(): void
    {
        $this->expectException(DatabaseException::class);

        $this->connection->switchDatabase('__nonexistent_database_xyz__');
    }

    public function testConnectWithBadHostThrowsDatabaseException(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = MysqlConnection::create(
            container: $container,
            config: new MysqlConnectionConfig(
                name: 'unreachable',
                host: '127.0.0.1',
                port: 1,
                username: 'nobody',
                password: 'nopass',
                database: 'nowhere',
                timeout: 1,
            ),
        );

        $this->expectException(DatabaseException::class);

        $connection->connect();
    }
}
