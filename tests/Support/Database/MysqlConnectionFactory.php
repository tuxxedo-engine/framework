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
use Tuxxedo\Database\Driver\Mysql\Config\MysqlConnectionConfig;
use Tuxxedo\Database\Driver\Mysql\MysqlConnection;

class MysqlConnectionFactory
{
    public static function create(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return MysqlConnection::create(
            container: $container,
            config: new MysqlConnectionConfig(
                name: 'test',
                role: $role,
                host: MysqlTestEnv::host(),
                port: MysqlTestEnv::port(),
                unixSocket: MysqlTestEnv::socket(),
                username: MysqlTestEnv::username(),
                password: MysqlTestEnv::password(),
                database: MysqlTestEnv::databaseName(),
                charset: MysqlTestEnv::charset(),
                timeout: MysqlTestEnv::timeout(),
            ),
        );
    }
}
