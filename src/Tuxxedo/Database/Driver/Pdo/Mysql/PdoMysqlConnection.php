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
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\MysqlDialect;

class PdoMysqlConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        ConfigInterface $config,
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
        ConfigInterface $config,
    ): string {
        if ($config->string('dsn') !== '') {
            return $config->string('dsn');
        }

        $database = '';
        $charset = '';

        if ($config->string('database') !== '') {
            $database = ';dbname=' . $config->string('database');
        }

        if ($config->string('options.charset') !== '') {
            $charset = ';charset=' . $config->string('options.charset');
        }

        if ($config->isString('unixSocket') && $config->string('unixSocket') !== '') {
            return \sprintf(
                'mysql:unix_socket=%s%s%s',
                $config->string('unixSocket'),
                $database,
                $charset,
            );
        }

        $port = '';

        if ($config->isInt('port')) {
            $port = ';port=' . $config->int('port');
        }

        return \sprintf(
            'mysql:host=%s%s%s%s',
            $config->string('host'),
            $port,
            $database,
            $charset,
        );
    }

    protected function getPdoOptions(
        ConfigInterface $config,
    ): array {
        $options = [
            \PDO::ATTR_TIMEOUT => $config->int('options.timeout'),
        ];

        if ($config->bool('ssl.enabled')) {
            if ($config->has('ssl.ca') && $config->string('ssl.ca') !== '') {
                $options[Mysql::ATTR_SSL_CA] = $config->string('ssl.ca');
            }

            if ($config->has('ssl.cert') && $config->string('ssl.cert') !== '') {
                $options[Mysql::ATTR_SSL_CERT] = $config->string('ssl.cert');
            }

            if ($config->has('ssl.key') && $config->string('ssl.key') !== '') {
                $options[Mysql::ATTR_SSL_KEY] = $config->string('ssl.key');
            }

            $options[Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = $config->bool('ssl.verifyPeer');
        }

        return $options;
    }
}
