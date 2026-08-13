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
use Tuxxedo\Database\Config\ConnectionConfigInterface;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;
use Tuxxedo\Database\Driver\Pdo\Mysql\Config\PdoMysqlConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\MysqlDialect;

class PdoMysqlConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        ConnectionConfigInterface $config,
    ): self {
        /** @var PdoMysqlConnectionConfigInterface $config */

        return new self($container, $config);
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

        if ($this->currentDatabase !== '') {
            $database = ';dbname=' . $this->currentDatabase;
        }

        if ($config->charset !== '') {
            $charset = ';charset=' . $config->charset;
        }

        if ($config->unixSocket !== null && $config->unixSocket !== '') {
            // @codeCoverageIgnoreStart
            return \sprintf(
                'mysql:unix_socket=%s%s%s',
                $config->unixSocket,
                $database,
                $charset,
            );
            // @codeCoverageIgnoreEnd
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
            // @codeCoverageIgnoreStart
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
            // @codeCoverageIgnoreEnd
        }

        return $options;
    }

    public function switchDatabase(
        string $database,
    ): void {
        $this->connectCheck();

        if ($this->isServerInTransaction()) {
            throw DatabaseException::fromCannotSwitchDatabaseInTransaction();
        }

        try {
            $this->pdo->exec(
                \sprintf(
                    'USE `%s`',
                    \str_replace('`', '``', $database),
                ),
            );
        } catch (\PDOException $exception) {
            self::throwFromPdoException($exception);
        }

        $this->currentDatabase = $database;
    }

    public function currentDatabase(): string
    {
        $this->connectCheck();

        $statement = $this->pdo->query('SELECT DATABASE()');

        if ($statement === false) {
            return $this->currentDatabase; // @codeCoverageIgnore
        }

        /** @var array{0: string|null}|false $row */
        $row = $statement->fetch(\PDO::FETCH_NUM);

        if ($row === false || $row[0] === null) {
            return ''; // @codeCoverageIgnore
        }

        return $row[0];
    }
}
