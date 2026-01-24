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

namespace Tuxxedo\Database\Driver\Pdo\Mysql;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;

class PdoMysqlConnection extends AbstractPdoConnection
{
    protected function getDriverName(): DefaultDriver
    {
        return DefaultDriver::PDO_MYSQL;
    }

    // @todo SSL options?
    protected function getDsn(
        ConfigInterface $config,
    ): string {
        if ($config->getString('dsn') !== '') {
            return $config->getString('dsn');
        }

        $database = '';
        $charset = '';

        if ($config->getString('database') !== '') {
            $database = ';dbname=' . $config->getString('database');
        }

        if ($config->getString('options.charset') !== '') {
            $charset = ';charset=' . $config->getString('options.charset');
        }

        if ($config->isString('unixSocket') && $config->getString('unixSocket') !== '') {
            return \sprintf(
                'mysql:unix_socket=%s%s%s',
                $config->getString('unixSocket'),
                $database,
                $charset,
            );
        }

        $port = '';

        if ($config->isInt('port')) {
            $port = ';port=' . $config->getInt('port');
        }

        return \sprintf(
            'mysql:host=%s%s%s%s',
            $config->getString('host'),
            $port,
            $database,
            $charset,
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
