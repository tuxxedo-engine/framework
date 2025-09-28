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

    private readonly \mysqli $mysqli;
    private bool $connected = false;
    private \Closure $connector;

    public function __construct(
        ConfigInterface $config,
    ) {
        $mysqli = \mysqli_init();

        if ($mysqli === false) {
            throw DatabaseException::fromCannotInitializeNativeDriver();
        }

        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = DefaultDriver::MYSQL;
        $this->mysqli = $mysqli;

        $this->connector = function () use ($config): void {
            if ($config->has('options.timeout')) {
                $timeout = $config->getInt('options.timeout');

                $this->mysqli->options(\MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
                $this->mysqli->options(\MYSQLI_OPT_READ_TIMEOUT, $timeout);
            }

            if ($config->has('unixSocket')) {
                $this->mysqli->real_connect(
                    socket: $config->getString('unixSocket'),
                );
            } else {
                $flags = 0;

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

                $this->mysqli->real_connect(
                    hostname: $config->getBool('options.persistent')
                        ? 'p:' . $config->getString('host')
                        : $config->getString('host'),
                    username: $config->getString('user'),
                    password: $config->getString('password'),
                    database: $config->has('database')
                        ? $config->getString('database')
                        : null,
                    port: $config->has('port')
                        ? $config->getInt('port')
                        : null,
                    flags: $flags,
                );
            }

            if ($this->mysqli->connect_errno !== 0) {
                throw DatabaseException::fromCannotConnect(
                    code: $this->mysqli->connect_errno,
                    error: $this->mysqli->connect_error ?? '',
                );
            }

            $this->mysqli->set_charset($config->getString('options.charset'));

            $this->connected = true;
        };

        if ($config->getBool('options.lazy')) {
            $this->connect();
        }
    }

    /**
     * @throws DatabaseException
     */
    private function connectCheck(): void
    {
        if (!$this->connected) {
            $this->connect();
        }
    }

    public function getDriverInstance(): \mysqli
    {
        return $this->mysqli;
    }

    public function connect(
        bool $reconnect = false,
    ): void {
        if ($reconnect || !$this->connected) {
            ($this->connector)();
        }
    }

    public function close(): void
    {
        if ($this->connected) {
            $this->mysqli->close();

            $this->connected = false;
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
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

    public function escape(
        string $value,
    ): string {
        $this->connectCheck();

        return $this->mysqli->real_escape_string($value);
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

        if (!$this->mysqli->begin_transaction()) {
            throw DatabaseException::fromError(
                sqlState: $this->mysqli->sqlstate,
                code: $this->mysqli->errno,
                error: $this->mysqli->error,
            );
        }
    }

    public function commit(): void
    {
        $this->connectCheck();

        if (!$this->mysqli->commit()) {
            throw DatabaseException::fromError(
                sqlState: $this->mysqli->sqlstate,
                code: $this->mysqli->errno,
                error: $this->mysqli->error,
            );
        }
    }

    public function rollback(): void
    {
        $this->connectCheck();

        if (!$this->mysqli->rollback()) {
            throw DatabaseException::fromError(
                sqlState: $this->mysqli->sqlstate,
                code: $this->mysqli->errno,
                error: $this->mysqli->error,
            );
        }
    }

    public function inTransaction(): bool
    {
        $this->connectCheck();

        $result = $this->mysqli->query('SELECT @@session.in_transaction');

        if (!$result instanceof \mysqli_result) {
            return false;
        }

        $row = $result->fetch_row();

        return isset($row[0]) && $row[0] === '1';
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

        $result = $this->mysqli->query($sql);

        if (\is_bool($result)) {
            if ($result === false) {
                throw DatabaseException::fromError(
                    sqlState: $this->mysqli->sqlstate,
                    code: $this->mysqli->errno,
                    error: $this->mysqli->error,
                );
            }

            $result = null;
        }

        $affectedRows = $this->mysqli->affected_rows;

        if (\is_string($affectedRows)) {
            throw DatabaseException::fromValueOverflow(
                value: $affectedRows,
            );
        }

        if ($affectedRows < 0) {
            $affectedRows = 0;
        }

        return new MysqlResultSet(
            result: $result,
            affectedRows: $affectedRows,
        );
    }
}
