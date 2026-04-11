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
        $options = [
            \PDO::ATTR_TIMEOUT => $config->getInt('options.timeout'),
        ];

        if ($config->getBool('ssl.enabled')) {
            if ($config->has('ssl.ca') && $config->getString('ssl.ca') !== '') {
                $options[\PDO::MYSQL_ATTR_SSL_CA] = $config->getString('ssl.ca');
            }

            if ($config->has('ssl.cert') && $config->getString('ssl.cert') !== '') {
                $options[\PDO::MYSQL_ATTR_SSL_CERT] = $config->getString('ssl.cert');
            }

            if ($config->has('ssl.key') && $config->getString('ssl.key') !== '') {
                $options[\PDO::MYSQL_ATTR_SSL_KEY] = $config->getString('ssl.key');
            }

            $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $config->getBool('ssl.verifyPeer');
        }

        return $options;
    }
}
