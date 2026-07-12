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

namespace Integration\Database\Driver\Pdo\Generic;

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Pdo\Generic\Config\PdoGenericConnectionConfig;
use Tuxxedo\Database\Driver\Pdo\Generic\PdoGenericConnection;

#[RequiresPhpExtension('pdo_sqlite')]
class PdoGenericConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return PdoGenericConnection::create(
            container: $container,
            config: new PdoGenericConnectionConfig(
                name: 'test',
                role: $role,
                dsn: 'sqlite::memory:',
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
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = PdoGenericConnection::create(
            container: $container,
            config: new PdoGenericConnectionConfig(
                name: 'eager',
                dsn: 'sqlite::memory:',
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

        $connection = PdoGenericConnection::create(
            container: $container,
            config: new PdoGenericConnectionConfig(
                name: 'invalid',
                dsn: 'invalidscheme:test',
            ),
        );

        $this->expectException(DatabaseException::class);

        $connection->connect();
    }
}
