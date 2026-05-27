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

namespace Tuxxedo\Database\Driver\Pdo\Pgsql;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\PgsqlDialect;

class PdoPgsqlConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        ConfigInterface $config,
    ): self {
        return new self($container, $config);
    }

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
        if ($config->string('dsn') !== '') {
            return $config->string('dsn');
        }

        $database = '';
        $port = '';
        $timeout = '';
        $sslMode = '';
        $sslParams = '';

        if ($config->string('database') !== '') {
            $database = ';dbname=' . $config->string('database');
        }

        if ($config->isInt('port')) {
            $port = ';port=' . $config->int('port');
        }

        if ($config->isInt('options.timeout')) {
            $timeout = ';connect_timeout=' . $config->int('options.timeout');
        }

        if ($config->bool('ssl.enabled')) {
            $mode = $config->string('ssl.mode');

            $sslMode = ';sslmode=' . ($mode !== '' ? $mode : 'require');

            if ($config->string('ssl.ca') !== '') {
                $sslParams .= ';sslrootcert=' . $config->string('ssl.ca');
            }

            if ($config->string('ssl.cert') !== '') {
                $sslParams .= ';sslcert=' . $config->string('ssl.cert');
            }

            if ($config->string('ssl.key') !== '') {
                $sslParams .= ';sslkey=' . $config->string('ssl.key');
            }
        } elseif ($config->string('ssl.mode') !== '') {
            $sslMode = ';sslmode=' . $config->string('ssl.mode');
        }

        return \sprintf(
            'pgsql:host=%s%s%s%s%s%s',
            $config->string('host'),
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
        $charset = $config->string('options.charset');

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
