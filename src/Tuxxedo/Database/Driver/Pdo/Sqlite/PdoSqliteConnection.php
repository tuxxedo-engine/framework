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

namespace Tuxxedo\Database\Driver\Pdo\Sqlite;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;
use Tuxxedo\Database\Driver\Pdo\Sqlite\Config\PdoSqliteConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\SqliteDialect;

class PdoSqliteConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        PdoSqliteConnectionConfigInterface $config,
    ): self {
        return new self($container, $config);
    }

    protected function getDriverDialect(): DialectInterface
    {
        return new SqliteDialect();
    }

    protected function getDsn(
        PdoConnectionConfigInterface $config,
    ): string {
        /** @var PdoSqliteConnectionConfigInterface $config */

        if ($config->dsn !== '') {
            return $config->dsn;
        }

        return \sprintf(
            'sqlite:%s',
            $config->database,
        );
    }

    protected function getPdoOptions(
        PdoConnectionConfigInterface $config,
    ): array {
        /** @var PdoSqliteConnectionConfigInterface $config */

        if ($config->timeout === null) {
            return [];
        }

        return [
            \PDO::ATTR_TIMEOUT => $config->timeout,
        ];
    }
}
