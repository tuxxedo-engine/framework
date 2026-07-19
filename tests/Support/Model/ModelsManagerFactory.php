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

namespace Support\Model;

use Tuxxedo\Container\Container;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Sqlite\Config\SqliteConnectionConfig;
use Tuxxedo\Database\Driver\Sqlite\SqliteConnection;
use Tuxxedo\Database\Hydrator\Hydrator as DatabaseHydrator;
use Tuxxedo\Model\DirtyTracker;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\MetaData;
use Tuxxedo\Model\ModelsManager;
use Tuxxedo\Model\ModelsManagerInterface;

class ModelsManagerFactory
{
    public static function create(): ModelsManagerInterface
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        $connection = SqliteConnection::create(
            container: $container,
            config: new SqliteConnectionConfig(
                name: 'test',
                database: ':memory:',
            ),
        );

        return self::wrap(
            container: $container,
            connection: $connection,
        );
    }

    public static function createFromConnection(
        ConnectionInterface $connection,
    ): ModelsManagerInterface {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return self::wrap(
            container: $container,
            connection: $connection,
        );
    }

    private static function wrap(
        Container $container,
        ConnectionInterface $connection,
    ): ModelsManagerInterface {
        return new ModelsManager(
            container: $container,
            connection: $connection,
            metaData: new MetaData(
                adapter: new ReflectionMetaDataAdapter(),
            ),
            dirtyTracker: new DirtyTracker(),
            databaseHydrator: new DatabaseHydrator(
                container: $container,
            ),
        );
    }
}
