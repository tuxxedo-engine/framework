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

namespace Tuxxedo\Database\Driver\Pdo\Pgsql;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;

class PdoPgsqlConnection extends AbstractPdoConnection
{
    protected function getDriverName(): DefaultDriver
    {
        return DefaultDriver::PDO_PGSQL;
    }

    // @todo SSL options?
    // @todo Charset?
    protected function getDsn(
        ConfigInterface $config,
    ): string {
        if ($config->getString('dsn') !== '') {
            return $config->getString('dsn');
        }

        $database = '';
        $port = '';

        if ($config->getString('database') !== '') {
            $database = ';dbname=' . $config->getString('database');
        }

        if ($config->isInt('port')) {
            $port = ';port=' . $config->getInt('port');
        }

        return \sprintf(
            'pgsql:host=%s%s%s',
            $config->getString('host'),
            $port,
            $database,
        );
    }
}
