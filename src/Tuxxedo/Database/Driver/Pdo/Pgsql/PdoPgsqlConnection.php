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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;
use Tuxxedo\Database\Driver\Pdo\Pgsql\Config\PdoPgsqlConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\PgsqlDialect;

class PdoPgsqlConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        PdoPgsqlConnectionConfigInterface $config,
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
        PdoConnectionConfigInterface $config,
    ): string {
        /** @var PdoPgsqlConnectionConfigInterface $config */

        if ($config->dsn !== '') {
            return $config->dsn;
        }

        $database = '';
        $port = '';
        $timeout = '';
        $sslMode = '';
        $sslParams = '';

        if ($config->database !== '') {
            $database = ';dbname=' . $config->database;
        }

        if ($config->port !== null) {
            $port = ';port=' . $config->port;
        }

        if ($config->timeout !== null) {
            $timeout = ';connect_timeout=' . $config->timeout;
        }

        if ($config->sslEnabled) {
            $sslMode = ';sslmode=' . ($config->sslMode !== '' ? $config->sslMode : 'require');

            if ($config->sslCa !== '') {
                $sslParams .= ';sslrootcert=' . $config->sslCa;
            }

            if ($config->sslCert !== '') {
                $sslParams .= ';sslcert=' . $config->sslCert;
            }

            if ($config->sslKey !== '') {
                $sslParams .= ';sslkey=' . $config->sslKey;
            }
        } elseif ($config->sslMode !== '') {
            $sslMode = ';sslmode=' . $config->sslMode;
        }

        return \sprintf(
            'pgsql:host=%s%s%s%s%s%s',
            $config->host,
            $port,
            $database,
            $timeout,
            $sslMode,
            $sslParams,
        );
    }

    protected function postConnectHook(
        PdoConnectionConfigInterface $config,
    ): void {
        /** @var PdoPgsqlConnectionConfigInterface $config */

        if ($config->charset === '') {
            return;
        }

        $this->pdo->exec(
            \sprintf(
                'SET client_encoding TO \'%s\'',
                \addcslashes($config->charset, "\\'"),
            ),
        );
    }
}
