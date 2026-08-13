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

namespace Integration\Database\Driver\Pgsql;

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\PgsqlConnectionFactory;
use Support\Database\PgsqlSchemaProvider;
use Support\Database\PgsqlTestEnv;
use Support\Database\RealDatabaseIntegrationSetup;
use Support\Database\SchemaProvider;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Pgsql\Config\PgsqlConnectionConfig;
use Tuxxedo\Database\Driver\Pgsql\PgsqlConnection;

#[RequiresPhpExtension('pgsql')]
class PgsqlConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    use RealDatabaseIntegrationSetup;

    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::pgsqlUnavailableReason();
    }

    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        return PgsqlConnectionFactory::create(
            role: $role,
        );
    }

    protected function schemaProvider(): SchemaProvider
    {
        return new PgsqlSchemaProvider();
    }

    public function testConfigWithLazyFalseConnectsEagerly(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PgsqlConnection::create(
            container: $container,
            config: new PgsqlConnectionConfig(
                name: 'eager',
                host: PgsqlTestEnv::host(),
                port: PgsqlTestEnv::port(),
                username: PgsqlTestEnv::username(),
                password: PgsqlTestEnv::password(),
                database: PgsqlTestEnv::databaseName(),
                lazy: false,
            ),
        );

        self::assertTrue(
            $connection->isConnected(),
        );

        $connection->close();
    }

    public function testConfigWithTimeoutIncludesConnectTimeoutInDsn(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PgsqlConnection::create(
            container: $container,
            config: new PgsqlConnectionConfig(
                name: 'with-timeout',
                host: PgsqlTestEnv::host(),
                port: PgsqlTestEnv::port(),
                username: PgsqlTestEnv::username(),
                password: PgsqlTestEnv::password(),
                database: PgsqlTestEnv::databaseName(),
                timeout: 5,
            ),
        );

        $connection->connect();

        self::assertTrue(
            $connection->isConnected(),
        );

        $connection->close();
    }

    public function testConfigWithPersistentUsesPconnect(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PgsqlConnection::create(
            container: $container,
            config: new PgsqlConnectionConfig(
                name: 'persistent',
                host: PgsqlTestEnv::host(),
                port: PgsqlTestEnv::port(),
                username: PgsqlTestEnv::username(),
                password: PgsqlTestEnv::password(),
                database: PgsqlTestEnv::databaseName(),
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

        $connection = PgsqlConnection::create(
            container: $container,
            config: new PgsqlConnectionConfig(
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

    public function testConnectWithBadHostThrowsDatabaseException(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PgsqlConnection::create(
            container: $container,
            config: new PgsqlConnectionConfig(
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

    public function testSwitchDatabaseUpdatesCurrentDatabase(): void
    {
        $original = $this->connection->currentDatabase();

        $this->connection->switchDatabase('postgres');

        self::assertSame(
            'postgres',
            $this->connection->currentDatabase(),
        );

        $this->connection->switchDatabase($original);
    }

    public function testSwitchDatabaseThrowsWhileInTransaction(): void
    {
        $this->connection->begin();

        try {
            $this->expectException(DatabaseException::class);

            $this->connection->switchDatabase('postgres');
        } finally {
            $this->connection->rollback();
        }
    }

    public function testSwitchDatabaseThrowsAfterRawBegin(): void
    {
        $this->connection->query(
            sql: 'BEGIN',
            native: true,
        );

        try {
            $this->expectException(DatabaseException::class);

            $this->connection->switchDatabase('postgres');
        } finally {
            $this->connection->query(
                sql: 'ROLLBACK',
                native: true,
            );
        }
    }

    public function testSwitchDatabaseThrowsOnNonexistentDatabase(): void
    {
        $this->expectException(DatabaseException::class);

        $this->connection->switchDatabase('__nonexistent_database_xyz__');
    }
}
