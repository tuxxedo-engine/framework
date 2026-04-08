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

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Dialect\DialectInterface;
use Tuxxedo\Database\Dialect\SqliteDialect;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;

class PdoSqliteConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        ConfigInterface $config,
    ): self {
        return new self($container, $config);
    }

    protected function getDriverName(): DefaultDriver
    {
        return DefaultDriver::PDO_SQLITE;
    }

    protected function getDriverDialect(): DialectInterface
    {
        return new SqliteDialect();
    }

    protected function getDsn(
        ConfigInterface $config,
    ): string {
        if ($config->getString('dsn') !== '') {
            return $config->getString('dsn');
        }

        return \sprintf(
            'sqlite:%s',
            $config->getString('database'),
        );
    }

    protected function getPdoOptions(
        ConfigInterface $config,
    ): array {
        return [
            \PDO::ATTR_TIMEOUT => $config->getInt('options.timeout'),
        ];
    }
}
