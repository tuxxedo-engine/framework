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
    protected function getDsn(
        ConfigInterface $config,
    ): string {
        if ($config->getString('dsn') !== '') {
            return $config->getString('dsn');
        }

        $database = '';
        $port = '';
        $timeout = '';

        if ($config->getString('database') !== '') {
            $database = ';dbname=' . $config->getString('database');
        }

        if ($config->isInt('port')) {
            $port = ';port=' . $config->getInt('port');
        }

        if ($config->isInt('options.timeout')) {
            $timeout = ';connect_timeout=' . $config->getInt('options.timeout');
        }

        return \sprintf(
            'pgsql:host=%s%s%s%s',
            $config->getString('host'),
            $port,
            $database,
            $timeout,
        );
    }

    protected function postConnectHook(
        ConfigInterface $config,
    ): void {
        $charset = $config->getString('options.charset');

        if ($charset === '') {
            return;
        }

        $this->pdo->exec(
            \sprintf(
                'SET client_encoding TO \'%s\'',
                \addcslashes($charset, "\\'"),
            ),
        );
    }
}
