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

namespace Tuxxedo\Database\Driver\Pdo\Mysql;

use Pdo\Mysql;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;
use Tuxxedo\Database\Driver\Pdo\Mysql\Config\PdoMysqlConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\MysqlDialect;

class PdoMysqlConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        PdoMysqlConnectionConfigInterface $config,
    ): self {
        return new self($container, $config);
    }

    protected function getDriverName(): DefaultDriver
    {
        return DefaultDriver::PDO_MYSQL;
    }

    protected function getDriverDialect(): DialectInterface
    {
        return new MysqlDialect();
    }

    protected function getDsn(
        PdoConnectionConfigInterface $config,
    ): string {
        /** @var PdoMysqlConnectionConfigInterface $config */

        if ($config->dsn !== '') {
            return $config->dsn;
        }

        $database = '';
        $charset = '';

        if ($config->database !== '') {
            $database = ';dbname=' . $config->database;
        }

        if ($config->charset !== '') {
            $charset = ';charset=' . $config->charset;
        }

        if ($config->unixSocket !== null && $config->unixSocket !== '') {
            return \sprintf(
                'mysql:unix_socket=%s%s%s',
                $config->unixSocket,
                $database,
                $charset,
            );
        }

        $port = '';

        if ($config->port !== null) {
            $port = ';port=' . $config->port;
        }

        return \sprintf(
            'mysql:host=%s%s%s%s',
            $config->host,
            $port,
            $database,
            $charset,
        );
    }

    protected function getPdoOptions(
        PdoConnectionConfigInterface $config,
    ): array {
        /** @var PdoMysqlConnectionConfigInterface $config */

        $options = [];

        if ($config->timeout !== null) {
            $options[\PDO::ATTR_TIMEOUT] = $config->timeout;
        }

        if ($config->sslEnabled) {
            if ($config->sslCa !== '') {
                $options[Mysql::ATTR_SSL_CA] = $config->sslCa;
            }

            if ($config->sslCert !== '') {
                $options[Mysql::ATTR_SSL_CERT] = $config->sslCert;
            }

            if ($config->sslKey !== '') {
                $options[Mysql::ATTR_SSL_KEY] = $config->sslKey;
            }

            $options[Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = $config->sslVerifyPeer;
        }

        return $options;
    }
}
