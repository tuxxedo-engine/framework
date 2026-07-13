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

namespace Unit\Database\Resolver;

use PHPUnit\Framework\TestCase;
use Support\Database\StubConnection;
use Support\Reflection\StubParameterReflector;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionManager;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Resolver\NamedConnection;

class NamedConnectionTest extends TestCase
{
    public function testResolveReturnsNamedConnectionFromManager(): void
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

        $resolver = new NamedConnection(
            name: 'shard-a',
        );

        $resolved = $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );

        self::assertSame(
            $connection,
            $resolved,
        );
    }

    public function testResolveUsesConstructorNameArgument(): void
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

        $resolver = new NamedConnection(
            name: 'shard-b',
        );

        $resolved = $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );

        self::assertSame(
            $shardB,
            $resolved,
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

        $resolver = new NamedConnection(
            name: 'nonexistent',
        );

        $this->expectException(DatabaseException::class);

        $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );
    }
}
