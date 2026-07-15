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

namespace Support\Database;

use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Pdo\Pgsql\Config\PdoPgsqlConnectionConfig;
use Tuxxedo\Database\Driver\Pdo\Pgsql\PdoPgsqlConnection;

class PdoPgsqlConnectionFactory
{
    public static function create(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return PdoPgsqlConnection::create(
            container: $container,
            config: new PdoPgsqlConnectionConfig(
                name: 'test',
                role: $role,
                host: PgsqlTestEnv::host(),
                port: PgsqlTestEnv::port(),
                username: PgsqlTestEnv::username(),
                password: PgsqlTestEnv::password(),
                database: PgsqlTestEnv::databaseName(),
                charset: PgsqlTestEnv::charset(),
                timeout: PgsqlTestEnv::timeout(),
            ),
        );
    }
}
