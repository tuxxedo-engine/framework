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
use Tuxxedo\Database\Dialect\DialectInterface;
use Tuxxedo\Database\Dialect\PgsqlDialect;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;

class PdoPgsqlConnection extends AbstractPdoConnection
{
    protected function getDriverName(): DefaultDriver
    {
        return DefaultDriver::PDO_PGSQL;
    }

    protected function getDriverDialect(): DialectInterface
    {
        return new PgsqlDialect();
    }

    protected function getDsn(
        ConfigInterface $config,
    ): string {
        if ($config->getString('dsn') !== '') {
            return $config->getString('dsn');
        }

        $database = '';
        $port = '';
        $timeout = '';
        $sslMode = '';
        $sslParams = '';

        if ($config->getString('database') !== '') {
            $database = ';dbname=' . $config->getString('database');
        }

        if ($config->isInt('port')) {
            $port = ';port=' . $config->getInt('port');
        }

        if ($config->isInt('options.timeout')) {
            $timeout = ';connect_timeout=' . $config->getInt('options.timeout');
        }

        if ($config->getBool('ssl.enabled')) {
            $mode = $config->getString('ssl.mode');

            $sslMode = ';sslmode=' . ($mode !== '' ? $mode : 'require');

            if ($config->getString('ssl.ca') !== '') {
                $sslParams .= ';sslrootcert=' . $config->getString('ssl.ca');
            }

            if ($config->getString('ssl.cert') !== '') {
                $sslParams .= ';sslcert=' . $config->getString('ssl.cert');
            }

            if ($config->getString('ssl.key') !== '') {
                $sslParams .= ';sslkey=' . $config->getString('ssl.key');
            }
        } elseif ($config->getString('ssl.mode') !== '') {
            $sslMode = ';sslmode=' . $config->getString('ssl.mode');
        }

        return \sprintf(
            'pgsql:host=%s%s%s%s%s%s',
            $config->getString('host'),
            $port,
            $database,
            $timeout,
            $sslMode,
            $sslParams,
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
