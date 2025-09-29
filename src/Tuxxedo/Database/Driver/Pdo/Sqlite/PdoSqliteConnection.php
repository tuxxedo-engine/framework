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

namespace Tuxxedo\Database\Driver\Pdo\Sqlite;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;

class PdoSqliteConnection extends AbstractPdoConnection
{
    protected function getDriverName(): DefaultDriver
    {
        return DefaultDriver::PDO_SQLITE;
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
}
