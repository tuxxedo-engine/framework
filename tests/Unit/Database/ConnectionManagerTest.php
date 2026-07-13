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

namespace Unit\Database;

use PHPUnit\Framework\TestCase;
use Support\Database\StubConnection;
use Support\Database\StubConnectionConfig;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\Config\ConnectionManagerConfig;
use Tuxxedo\Database\ConnectionManager;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;

class ConnectionManagerTest extends TestCase
{
    public function testEmptyManagerHasNoConnections(): void
    {
        $manager = new ConnectionManager();

        self::assertSame(
            [],
            $manager->connections,
        );
    }

    public function testRegisterConnectionAppendsToList(): void
    {
        $manager = new ConnectionManager();
        $connection = new StubConnection(
            name: 'primary',
        );

        $manager->registerConnection(
            connection: $connection,
        );

        self::assertSame(
            [
                $connection,
            ],
            $manager->connections,
        );
    }

    public function testRegisterConnectionReturnsSelf(): void
    {
        $manager = new ConnectionManager();

        $returned = $manager->registerConnection(
            connection: new StubConnection(),
        );

        self::assertSame(
            $manager,
            $returned,
        );
    }

    public function testGetNamedConnectionReturnsRegisteredConnection(): void
    {
        $manager = new ConnectionManager();
        $connection = new StubConnection(
            name: 'shard-a',
        );

        $manager->registerConnection(
            connection: $connection,
        );

        self::assertSame(
            $connection,
            $manager->getNamedConnection(
                name: 'shard-a',
            ),
        );
    }

    public function testDefaultRoleConnectionAvailableViaGetDefault(): void
    {
        $manager = new ConnectionManager();
        $connection = new StubConnection(
            name: 'primary',
            role: ConnectionRole::DEFAULT,
        );

        $manager->registerConnection(
            connection: $connection,
        );

        self::assertSame(
            $connection,
            $manager->getDefaultConnection(),
        );
    }

    public function testDefaultReadRoleConnectionAvailableViaGetRead(): void
    {
        $manager = new ConnectionManager();
        $connection = new StubConnection(
            name: 'replica',
            role: ConnectionRole::DEFAULT_READ,
        );

        $manager->registerConnection(
            connection: $connection,
        );

        self::assertSame(
            $connection,
            $manager->getReadConnection(),
        );
    }

    public function testDefaultWriteRoleConnectionAvailableViaGetWrite(): void
    {
        $manager = new ConnectionManager();
        $connection = new StubConnection(
            name: 'primary',
            role: ConnectionRole::DEFAULT_WRITE,
        );

        $manager->registerConnection(
            connection: $connection,
        );

        self::assertSame(
            $connection,
            $manager->getWriteConnection(),
        );
    }

    public function testNoneRoleConnectionDoesNotAffectDefaults(): void
    {
        $manager = new ConnectionManager();

        $manager->registerConnection(
            connection: new StubConnection(
                name: 'ancillary',
                role: ConnectionRole::NONE,
            ),
        );

        $this->expectException(DatabaseException::class);

        $manager->getDefaultConnection();
    }

    public function testGetDefaultConnectionThrowsWhenNoneRegistered(): void
    {
        $manager = new ConnectionManager();

        $this->expectException(DatabaseException::class);

        $manager->getDefaultConnection();
    }

    public function testGetReadConnectionThrowsWhenNoneRegistered(): void
    {
        $manager = new ConnectionManager();

        $this->expectException(DatabaseException::class);

        $manager->getReadConnection();
    }

    public function testGetWriteConnectionThrowsWhenNoneRegistered(): void
    {
        $manager = new ConnectionManager();

        $this->expectException(DatabaseException::class);

        $manager->getWriteConnection();
    }

    public function testGetNamedConnectionThrowsForUnknownName(): void
    {
        $manager = new ConnectionManager();

        $this->expectException(DatabaseException::class);

        $manager->getNamedConnection(
            name: 'unknown',
        );
    }

    public function testLatestDefaultConnectionOverridesEarlier(): void
    {
        $manager = new ConnectionManager();
        $first = new StubConnection(
            name: 'first',
            role: ConnectionRole::DEFAULT,
        );

        $second = new StubConnection(
            name: 'second',
            role: ConnectionRole::DEFAULT,
        );

        $manager->registerConnection(
            connection: $first,
        );

        $manager->registerConnection(
            connection: $second,
        );

        self::assertSame(
            $second,
            $manager->getDefaultConnection(),
        );
    }

    public function testCreateFromConfigInstantiatesAndRegistersEachDriver(): void
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $manager = ConnectionManager::createFromConfig(
            container: $container,
            config: new ConnectionManagerConfig(
                connections: [
                    new StubConnectionConfig(
                        name: 'primary',
                        role: ConnectionRole::DEFAULT,
                    ),
                    new StubConnectionConfig(
                        name: 'replica',
                        role: ConnectionRole::DEFAULT_READ,
                    ),
                ],
            ),
        );

        self::assertCount(
            2,
            $manager->connections,
        );

        self::assertSame(
            'primary',
            $manager->getDefaultConnection()->name,
        );

        self::assertSame(
            'replica',
            $manager->getReadConnection()->name,
        );

        self::assertSame(
            'primary',
            $manager->getNamedConnection(
                name: 'primary',
            )->name,
        );
    }
}
