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
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Resolver\WriteConnection;

class WriteConnectionTest extends TestCase
{
    public function testResolveReturnsWriteConnectionFromManager(): void
    {
        $connection = new StubConnection(
            name: 'primary',
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

        $resolver = new WriteConnection();

        $resolved = $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );

        self::assertSame(
            $connection,
            $resolved,
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

        $resolver = new WriteConnection();

        $this->expectException(DatabaseException::class);

        $resolver->resolve(
            container: $container,
            parameter: new StubParameterReflector(),
        );
    }
}
