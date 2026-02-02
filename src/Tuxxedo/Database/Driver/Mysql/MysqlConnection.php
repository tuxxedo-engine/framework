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

namespace Tuxxedo\Database\Driver\Mysql;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

class MysqlConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;

    private \mysqli $mysqli;
    private readonly \Closure $connector;

    private bool $inTransaction = false;

    public function __construct(
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = DefaultDriver::MYSQL;

        $this->connector = function () use ($config): void {
            if (!isset($this->mysqli)) {
                $mysqli = \mysqli_init();

                if ($mysqli === false) {
                    throw DatabaseException::fromCannotInitializeNativeDriver();
                }

                $this->mysqli = $mysqli;
            }

            if ($config->has('options.timeout')) {
                $timeout = $config->getInt('options.timeout');

                $this->mysqli->options(\MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
                $this->mysqli->options(\MYSQLI_OPT_READ_TIMEOUT, $timeout);
            }

            if ($config->isString('unixSocket')) {
                $this->mysqli->real_connect(
                    socket: $config->getString('unixSocket'),
                );
            } else {
                $flags = $config->isInt('options.flags')
                    ? $config->getInt('options.flags')
                    : 0;

                if ($config->getBool('ssl.enabled')) {
                    $ca = $config->getString('ssl.ca');
                    $cert = $config->getString('ssl.cert');
                    $key = $config->getString('ssl.key');

                    if (
                        $ca !== '' &&
                        $cert !== '' &&
                        $key !== ''
                    ) {
                        $this->mysqli->ssl_set($key, $cert, $ca, null, null);
                    }

                    $flags |= \MYSQLI_CLIENT_SSL;

                    if (!$config->getBool('ssl.verifyPeer')) {
                        $flags |= \MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
                    }
                }

                try {
                    $this->mysqli->real_connect(
                        hostname: $config->getBool('options.persistent')
                            ? 'p:' . $config->getString('host')
                            : $config->getString('host'),
                        username: $config->getString('username'),
                        password: $config->getString('password'),
                        database: $config->has('database')
                            ? $config->getString('database')
                            : null,
                        port: $config->has('port')
                            ? $config->getInt('port')
                            : null,
                        flags: $flags,
                    );
                } finally {
                    if ($this->mysqli->connect_errno !== 0) {
                        throw DatabaseException::fromCannotConnect(
                            code: $this->mysqli->connect_errno,
                            error: $this->mysqli->connect_error ?? 'Connection error',
                        );
                    }
                }
            }

            $this->mysqli->set_charset($config->getString('options.charset'));
        };

        if (!$config->getBool('options.lazy')) {
            $this->connect();
        }
    }

    /**
     * @throws DatabaseException
     */
    private function connectCheck(): void
    {
        if (!isset($this->mysqli)) {
            $this->connect();
        }
    }

    /**
     * @throws DatabaseException
     */
    public function throwFromMysqlException(
        \mysqli_sql_exception $exception,
    ): never {
        throw DatabaseException::fromError(
            sqlState: $exception->getSqlState(),
            code: $exception->getCode(),
            error: $exception->getMessage(),
        );
    }

    public function throwFromLastError(
        \mysqli $mysqli,
    ): never {
        throw DatabaseException::fromError(
            sqlState: $mysqli->sqlstate,
            code: $mysqli->errno,
            error: $mysqli->error,
        );
    }

    public function isMariaDb(): bool
    {
        return \str_contains(\strtolower($this->serverVersion()), 'mariadb');
    }

    public function getDriverInstance(): \mysqli
    {
        $this->connectCheck();

        return $this->mysqli;
    }

    public function connect(
        bool $reconnect = false,
    ): void {
        if ($reconnect || !isset($this->mysqli)) {
            ($this->connector)();
        }
    }

    public function close(): void
    {
        if (isset($this->mysqli)) {
            $this->mysqli->close();

            unset($this->mysqli);
        }
    }

    public function isConnected(): bool
    {
        return isset($this->mysqli);
    }

    public function ping(): bool
    {
        try {
            $this->connectCheck();
        } catch (DatabaseException) {
            return false;
        }

        if ($this->mysqli->query('SELECT 1') instanceof \mysqli_result) {
            return true;
        }

        if ($this->mysqli->errno === 2006 || $this->mysqli->errno === 2013) {
            try {
                $this->connect(
                    reconnect: true,
                );
            } catch (DatabaseException) {
                return false;
            }

            if ($this->mysqli->query('SELECT 1') instanceof \mysqli_result) {
                return true;
            }
        }

        return false;
    }

    public function serverVersion(): string
    {
        $this->connectCheck();

        return $this->mysqli->server_info;
    }

    public function lastInsertIdAsString(
        ?string $sequence = null,
    ): ?string {
        $this->connectCheck();

        $id = $this->mysqli->insert_id;

        if ($id !== '' && $id !== 0) {
            return (string) $id;
        }

        return null;
    }

    public function lastInsertIdAsInt(
        ?string $sequence = null,
    ): ?int {
        $this->connectCheck();

        $id = $this->mysqli->insert_id;

        if ($id !== '' && $id !== 0) {
            return (int) $id;
        }

        return null;
    }

    public function begin(): void
    {
        $this->connectCheck();

        if (!$this->mysqli->begin_transaction(\MYSQLI_TRANS_START_READ_WRITE)) {
            $this->inTransaction = false;

            $this->throwFromLastError($this->mysqli);
        }

        $this->inTransaction = true;
    }

    public function commit(): void
    {
        $this->connectCheck();

        if (!$this->mysqli->commit()) {
            $this->inTransaction = false;

            $this->throwFromLastError($this->mysqli);
        }

        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        $this->connectCheck();

        if (!$this->mysqli->rollback()) {
            $this->inTransaction = false;

            $this->throwFromLastError($this->mysqli);
        }

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function transaction(
        \Closure $transaction,
    ): void {
        try {
            $this->begin();

            $transaction($this);

            $this->commit();
        } catch (\Exception $exception) {
            $this->rollback();

            throw $exception;
        }
    }

    public function prepare(
        string $sql,
    ): MysqlStatement {
        $this->connectCheck();

        return new MysqlStatement(
            connection: $this,
            sql: $sql,
        );
    }

    public function execute(
        string $sql,
        array $parameters = [],
    ): MysqlResultSet {
        return $this->prepare($sql)->execute($parameters);
    }

    public function query(
        string $sql,
    ): MysqlResultSet {
        $this->connectCheck();

        try {
            $result = $this->mysqli->query($sql);
        } catch (\mysqli_sql_exception $exception) {
            $this->throwFromMysqlException($exception);
        }

        if (\is_bool($result)) {
            if ($result === false) {
                $this->throwFromLastError($this->mysqli);
            }

            $result = null;
        }

        return new MysqlResultSet(
            result: $result,
            affectedRows: $this->mysqli->affected_rows,
        );
    }
}
