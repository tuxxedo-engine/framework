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
use Tuxxedo\Database\Config\ConnectionConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractConnection;
use Tuxxedo\Database\Driver\Mysql\Config\MysqlConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\MysqlDialect;
use Tuxxedo\Database\Query\Parser\StatementParser;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;

class MysqlConnection extends AbstractConnection
{
    public readonly string $name;
    public readonly ConnectionRole $role;
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
        $this->dialect = new MysqlDialect();
        $this->statementParser = new StatementParser(
            dialect: $this->dialect,
        );

        $this->connector = function () use ($config): void {
            if (!isset($this->mysqli)) {
                $mysqli = \mysqli_init();

                if ($mysqli === false) {
                    // @codeCoverageIgnoreStart
                    throw DatabaseException::fromCannotInitializeNativeDriver();
                    // @codeCoverageIgnoreEnd
                }

                $this->mysqli = $mysqli;
            }

            $this->mysqli->options(\MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

            if ($config->timeout !== null) {
                $this->mysqli->options(\MYSQLI_OPT_CONNECT_TIMEOUT, $config->timeout);
                $this->mysqli->options(\MYSQLI_OPT_READ_TIMEOUT, $config->timeout);
            }

            if ($config->unixSocket !== null) {
                // @codeCoverageIgnoreStart
                $this->mysqli->real_connect(
                    socket: $config->unixSocket,
                );
                // @codeCoverageIgnoreEnd
            } else {
                $flags = $config->flags ?? 0;

                if ($config->sslEnabled) {
                    // @codeCoverageIgnoreStart
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
                    // @codeCoverageIgnoreEnd
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
                } catch (\mysqli_sql_exception $exception) {
                    $connectErrno = $this->mysqli->connect_errno;
                    $connectError = $this->mysqli->connect_error;

                    unset($this->mysqli);

                    throw DatabaseException::fromCannotConnect(
                        code: $connectErrno !== 0
                            ? $connectErrno
                            : $exception->getCode(),
                        error: $connectError ?? $exception->getMessage(),
                    );
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
        ConnectionConfigInterface $config,
    ): self {
        /** @var MysqlConnectionConfigInterface $config */

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

    /**
     * @codeCoverageIgnore
     */
    public function throwFromLastError(
        \mysqli|\mysqli_stmt $mysqli,
    ): never {
        throw DatabaseException::fromError(
            sqlState: $mysqli->sqlstate,
            code: $mysqli->errno,
            error: $mysqli->error,
        );
    }

    /**
     * @throws DatabaseException
     */
    private function throwFromMysqliException(
        \mysqli_sql_exception $exception,
    ): never {
        $sqlState = $exception->getSqlState();

        throw DatabaseException::fromError(
            sqlState: $sqlState !== ''
                ? $sqlState
                : 'HY000',
            code: $exception->getCode(),
            error: $exception->getMessage(),
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
        if ($reconnect && isset($this->mysqli)) {
            $this->mysqli->close();

            unset($this->mysqli);
        }

        if (!isset($this->mysqli)) {
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

        // @codeCoverageIgnoreStart
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
        // @codeCoverageIgnoreEnd
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

        if ($this->inTransaction) {
            throw DatabaseException::fromAlreadyInTransaction();
        }

        try {
            $this->mysqli->begin_transaction(\MYSQLI_TRANS_START_READ_WRITE);
        } catch (\mysqli_sql_exception $exception) { // @codeCoverageIgnore
            $this->throwFromMysqliException($exception); // @codeCoverageIgnore
        }

        $this->inTransaction = true;
    }

    public function commit(): void
    {
        $this->connectCheck();

        if (!$this->inTransaction) {
            throw DatabaseException::fromNotInTransaction();
        }

        try {
            $this->mysqli->commit();
        } catch (\mysqli_sql_exception $exception) { // @codeCoverageIgnore
            $this->throwFromMysqliException($exception); // @codeCoverageIgnore
        }

        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        $this->connectCheck();

        if (!$this->inTransaction) {
            throw DatabaseException::fromNotInTransaction();
        }

        try {
            $this->mysqli->rollback();
        } catch (\mysqli_sql_exception $exception) { // @codeCoverageIgnore
            $this->throwFromMysqliException($exception); // @codeCoverageIgnore
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

            if (\is_bool($value)) {
                $bindingTypes .= 'i';
                $bindingValues[] = (int) $value;

                continue;
            }

            $bindingTypes .= match (true) {
                \is_int($value) => 'i',
                \is_float($value) => 'd',
                default => 's',
            };

            $bindingValues[] = $value;
        }

        try {
            if ($bindingTypes === '') {
                $directResult = $this->mysqli->query($sql);

                return new MysqlResultSet(
                    container: $this->container,
                    result: $directResult instanceof \mysqli_result
                        ? $directResult
                        : null,
                    affectedRows: (int) $this->mysqli->affected_rows,
                );
            }

            $statement = $this->mysqli->prepare($sql);

            if ($statement === false) {
                $this->throwFromLastError($this->mysqli); // @codeCoverageIgnore
            }

            $statement->bind_param($bindingTypes, ...$bindingValues);
            $statement->execute();

            $result = $statement->get_result();

            return new MysqlResultSet(
                container: $this->container,
                result: $result instanceof \mysqli_result
                    ? $result
                    : null,
                affectedRows: (int) $statement->affected_rows,
            );
        } catch (\mysqli_sql_exception $exception) {
            $this->throwFromMysqliException($exception);
        }
    }
}
