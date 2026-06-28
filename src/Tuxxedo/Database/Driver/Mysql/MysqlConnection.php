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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractConnection;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Mysql\Config\MysqlConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\MysqlDialect;
use Tuxxedo\Database\Query\Parser\StatementParser;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;

class MysqlConnection extends AbstractConnection
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;
    public readonly DialectInterface $dialect;

    private \mysqli $mysqli;
    private readonly \Closure $connector;
    private bool $inTransaction = false;

    public readonly StatementParserInterface $statementParser;

    private function __construct(
        private readonly ContainerInterface $container,
        MysqlConnectionConfigInterface $config,
    ) {
        $this->name = $config->name;
        $this->role = $config->role;
        $this->driver = DefaultDriver::MYSQL;
        $this->dialect = new MysqlDialect();
        $this->statementParser = new StatementParser(
            dialect: $this->dialect,
        );

        $this->connector = function () use ($config): void {
            if (!isset($this->mysqli)) {
                $mysqli = \mysqli_init();

                if ($mysqli === false) {
                    throw DatabaseException::fromCannotInitializeNativeDriver();
                }

                $this->mysqli = $mysqli;
            }

            if ($config->timeout !== null) {
                $this->mysqli->options(\MYSQLI_OPT_CONNECT_TIMEOUT, $config->timeout);
                $this->mysqli->options(\MYSQLI_OPT_READ_TIMEOUT, $config->timeout);
            }

            if ($config->unixSocket !== null) {
                $this->mysqli->real_connect(
                    socket: $config->unixSocket,
                );
            } else {
                $flags = $config->flags ?? 0;

                if ($config->sslEnabled) {
                    if (
                        $config->sslCa !== '' &&
                        $config->sslCert !== '' &&
                        $config->sslKey !== ''
                    ) {
                        $this->mysqli->ssl_set($config->sslKey, $config->sslCert, $config->sslCa, null, null);
                    }

                    $flags |= \MYSQLI_CLIENT_SSL;

                    if (!$config->sslVerifyPeer) {
                        $flags |= \MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
                    }
                }

                try {
                    $this->mysqli->real_connect(
                        hostname: $config->persistent
                            ? 'p:' . $config->host
                            : $config->host,
                        username: $config->username,
                        password: $config->password,
                        database: $config->database,
                        port: $config->port,
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

            $this->mysqli->set_charset($config->charset);
        };

        if (!$config->lazy) {
            $this->connect();
        }
    }

    public static function create(
        ContainerInterface $container,
        MysqlConnectionConfigInterface $config,
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

    public function query(
        string $sql,
        array $parameters = [],
        bool $native = false,
    ): MysqlResultSet {
        $this->connectCheck();

        $bindingTypes = '';
        $bindingValues = [];

        if (!$native) {
            $parsedStatement = $this->statementParser->parse($sql, $parameters);
            $sql = $parsedStatement->sql;
            $parameters = $parsedStatement->parameters;
        }

        foreach ($parameters as $value) {
            if (\is_array($value)) {
                continue;
            }

            $bindingTypes .= match (true) {
                \is_int($value) => 'i',
                \is_float($value) => 'f',
                \is_bool($value) => 'b',
                default => 's',
            };

            $bindingValues[] = $value;
        }

        $statement = $this->mysqli->prepare($sql);

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
