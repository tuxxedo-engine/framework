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

namespace Tuxxedo\Database\Driver\Mysql;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Dialect\MysqlDialect;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\StatementParser;
use Tuxxedo\Database\Driver\StatementParserInterface;

class MysqlConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;

    private \mysqli $mysqli;
    private readonly \Closure $connector;
    private StatementParserInterface $statementParser;

    private bool $inTransaction = false;

    private function __construct(
        private readonly ContainerInterface $container,
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
                        $exception = DatabaseException::fromCannotConnect(
                            code: $this->mysqli->connect_errno,
                            error: $this->mysqli->connect_error ?? 'Connection error',
                        );

                        unset($this->mysqli);

                        throw $exception;
                    }
                }
            }

            $this->mysqli->set_charset($config->getString('options.charset'));

            if (!isset($this->statementParser)) {
                $this->statementParser = new StatementParser(
                    dialect: new MysqlDialect(),
                );
            }
        };

        if (!$config->getBool('options.lazy')) {
            $this->connect();
        }
    }

    public static function create(
        ContainerInterface $container,
        ConfigInterface $config,
    ): self {
        return new self($container, $config);
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

    public function throwFromLastError(
        \mysqli|\mysqli_stmt $mysqli,
    ): never {
        throw DatabaseException::fromError(
            sqlState: $mysqli->sqlstate,
            code: $mysqli->errno,
            error: $mysqli->error,
        );
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

    public function lastInsertIdAsString(): ?string
    {
        $this->connectCheck();

        $id = $this->mysqli->insert_id;

        if ($id !== '' && $id !== 0) {
            return (string) $id;
        }

        return null;
    }

    public function lastInsertIdAsInt(): ?int
    {
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

    public function query(
        string $sql,
        array $parameters = [],
    ): MysqlResultSet {
        $this->connectCheck();

        $bindingTypes = '';
        $bindingValues = [];

        $parsedStatement = $this->statementParser->parse($sql, $parameters);

        foreach ($parsedStatement->bindings as $value) {
            $bindingTypes .= match (true) {
                \is_int($value) => 'i',
                \is_float($value) => 'f',
                \is_bool($value) => 'b',
                default => 's',
            };

            $bindingValues[] = $value;
        }

        $statement = $this->mysqli->prepare($parsedStatement->sql);

        if ($statement === false) {
            $this->throwFromLastError($this->mysqli);
        }

        if ($bindingTypes !== '') {
            $statement->bind_param($bindingTypes, ...$bindingValues);
        }

        if (!$statement->execute() || ($result = $statement->get_result()) === false) {
            if ($statement->errno !== 0) {
                $this->throwFromLastError($statement);
            }

            $result = null;
        }

        return new MysqlResultSet(
            container: $this->container,
            result: $result,
            affectedRows: (int) $statement->affected_rows,
        );
    }
}
