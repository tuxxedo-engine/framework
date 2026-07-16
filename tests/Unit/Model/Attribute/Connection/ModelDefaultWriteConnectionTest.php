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
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Hydrator\Hydrator as DatabaseHydrator;
use Tuxxedo\Model\Attribute\Connection\ModelDefaultWriteConnection;
use Tuxxedo\Model\DirtyTracker;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\MetaData;
use Tuxxedo\Model\ModelsManagerInterface;

class ModelDefaultWriteConnectionTest extends TestCase
{
    public function testResolveReturnsModelsManagerBoundToWriteConnection(): void
    {
        $connection = new StubConnection(
            name: 'writer',
            role: ConnectionRole::DEFAULT_WRITE,
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

        $resolver = new ModelDefaultWriteConnection();
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

    public function testResolvePropagatesExceptionWhenNoWriteConnection(): void
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

        $resolver = new ModelDefaultWriteConnection();

        $this->expectException(DatabaseException::class);

        $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );
    }
}
