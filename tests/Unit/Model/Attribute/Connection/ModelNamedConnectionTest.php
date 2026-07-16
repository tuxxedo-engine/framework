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

namespace Unit\Model\Attribute\Connection;

use PHPUnit\Framework\TestCase;
use Support\Database\StubConnection;
use Support\Reflection\StubParameterReflector;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionManager;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Hydrator\Hydrator as DatabaseHydrator;
use Tuxxedo\Model\Attribute\Connection\ModelNamedConnection;
use Tuxxedo\Model\DirtyTracker;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\MetaData;
use Tuxxedo\Model\ModelsManagerInterface;

class ModelNamedConnectionTest extends TestCase
{
    public function testResolveReturnsModelsManagerBoundToNamedConnection(): void
    {
        $connection = new StubConnection(
            name: 'shard-a',
        );

        $manager = new ConnectionManager();
        $manager->registerConnection(
            connection: $connection,
        );

        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $container->singleton(
            class: $manager,
        );

        $container->singleton(
            class: new MetaData(
                adapter: new ReflectionMetaDataAdapter(),
            ),
        );

        $container->singleton(
            class: new DirtyTracker(),
        );

        $container->singleton(
            class: new DatabaseHydrator(
                container: $container,
            ),
        );

        $resolver = new ModelNamedConnection(
            name: 'shard-a',
        );

        $resolved = $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );

        self::assertInstanceOf(
            ModelsManagerInterface::class,
            $resolved,
        );

        self::assertSame(
            $connection,
            $resolved->connection,
        );
    }

    public function testResolveDistinguishesMultipleNamedConnections(): void
    {
        $shardA = new StubConnection(
            name: 'shard-a',
        );

        $shardB = new StubConnection(
            name: 'shard-b',
        );

        $manager = new ConnectionManager();
        $manager->registerConnection(
            connection: $shardA,
        );

        $manager->registerConnection(
            connection: $shardB,
        );

        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $container->singleton(
            class: $manager,
        );

        $container->singleton(
            class: new MetaData(
                adapter: new ReflectionMetaDataAdapter(),
            ),
        );

        $container->singleton(
            class: new DirtyTracker(),
        );

        $container->singleton(
            class: new DatabaseHydrator(
                container: $container,
            ),
        );

        $resolver = new ModelNamedConnection(
            name: 'shard-b',
        );

        $resolved = $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );

        self::assertSame(
            $shardB,
            $resolved->connection,
        );
    }

    public function testResolvePropagatesExceptionForUnknownName(): void
    {
        $manager = new ConnectionManager();

        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $container->singleton(
            class: $manager,
        );

        $container->singleton(
            class: new MetaData(
                adapter: new ReflectionMetaDataAdapter(),
            ),
        );

        $container->singleton(
            class: new DirtyTracker(),
        );

        $container->singleton(
            class: new DatabaseHydrator(
                container: $container,
            ),
        );

        $resolver = new ModelNamedConnection(
            name: 'nonexistent',
        );

        $this->expectException(DatabaseException::class);

        $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );
    }
}
