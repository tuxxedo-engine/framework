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

namespace Integration\Database\Driver\Pdo\Pgsql;

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\PdoPgsqlConnectionFactory;
use Support\Database\PgsqlSchemaProvider;
use Support\Database\PgsqlTestEnv;
use Support\Database\RealDatabaseIntegrationSetup;
use Support\Database\SchemaProvider;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Pdo\Pgsql\Config\PdoPgsqlConnectionConfig;
use Tuxxedo\Database\Driver\Pdo\Pgsql\PdoPgsqlConnection;

#[RequiresPhpExtension('pdo_pgsql')]
class PdoPgsqlConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    use RealDatabaseIntegrationSetup;

    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::pgsqlUnavailableReason();
    }

    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        return PdoPgsqlConnectionFactory::create(
            role: $role,
        );
    }

    protected function schemaProvider(): SchemaProvider
    {
        return new PgsqlSchemaProvider();
    }

    public function testConfigWithExplicitDsnBypassesHostFieldConstruction(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $port = PgsqlTestEnv::port();
        $dsn = \sprintf(
            'pgsql:host=%s%s;dbname=%s',
            PgsqlTestEnv::host(),
            $port !== null
                ? ';port=' . $port
                : '',
            PgsqlTestEnv::databaseName(),
        );

        $connection = PdoPgsqlConnection::create(
            container: $container,
            config: new PdoPgsqlConnectionConfig(
                name: 'explicit-dsn',
                dsn: $dsn,
                username: PgsqlTestEnv::username(),
                password: PgsqlTestEnv::password(),
                charset: '',
            ),
        );

        $connection->connect();

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

        $connection = PdoPgsqlConnection::create(
            container: $container,
            config: new PdoPgsqlConnectionConfig(
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

    public function testConfigWithInvalidCharsetThrowsDatabaseException(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PdoPgsqlConnection::create(
            container: $container,
            config: new PdoPgsqlConnectionConfig(
                name: 'bad-charset',
                host: PgsqlTestEnv::host(),
                port: PgsqlTestEnv::port(),
                username: PgsqlTestEnv::username(),
                password: PgsqlTestEnv::password(),
                database: PgsqlTestEnv::databaseName(),
                charset: "utf8'; DROP TABLE users; --",
            ),
        );

        $this->expectException(DatabaseException::class);

        $connection->connect();
    }
}
