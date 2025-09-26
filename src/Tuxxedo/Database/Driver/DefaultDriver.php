<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Database\Driver;

use Tuxxedo\Database\Driver\Mysql\MysqlConnection;
use Tuxxedo\Database\Driver\Pdo\Generic\PdoGenericConnection;
use Tuxxedo\Database\Driver\Pdo\Mysql\PdoMysqlConnection;
use Tuxxedo\Database\Driver\Pdo\Pgsql\PdoPgsqlConnection;
use Tuxxedo\Database\Driver\Pdo\Sqlite\PdoSqliteConnection;
use Tuxxedo\Database\Driver\Pgsql\PgsqlConnection;
use Tuxxedo\Database\Driver\Sqlite\SqliteConnection;

enum DefaultDriver
{
    case MYSQL;
    case PDO;
    case PDO_MYSQL;
    case PDO_PGSQL;
    case PDO_SQLITE;
    case PGSQL;
    case SQLITE;

    /**
     * @return class-string<ConnectionInterface>
     */
    public function getDriverClass(): string
    {
        return match ($this) {
            self::MYSQL => MysqlConnection::class,
            self::PDO => PdoGenericConnection::class,
            self::PDO_MYSQL => PdoMysqlConnection::class,
            self::PDO_PGSQL => PdoPgsqlConnection::class,
            self::PDO_SQLITE => PdoSqliteConnection::class,
            self::PGSQL => PgsqlConnection::class,
            self::SQLITE => SqliteConnection::class,
        };
    }
}
