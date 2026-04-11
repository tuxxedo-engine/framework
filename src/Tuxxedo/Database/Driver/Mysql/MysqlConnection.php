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
use Tuxxedo\Database\Builder\CountBuilder;
use Tuxxedo\Database\Builder\CountBuilderInterface;
use Tuxxedo\Database\Builder\DeleteBuilder;
use Tuxxedo\Database\Builder\DeleteBuilderInterface;
use Tuxxedo\Database\Builder\Dialect\DialectInterface;
use Tuxxedo\Database\Builder\Dialect\MysqlDialect;
use Tuxxedo\Database\Builder\ExistsBuilder;
use Tuxxedo\Database\Builder\InsertBuilder;
use Tuxxedo\Database\Builder\InsertBuilderInterface;
use Tuxxedo\Database\Builder\Parser\StatementParser;
use Tuxxedo\Database\Builder\Parser\StatementParserInterface;
use Tuxxedo\Database\Builder\SelectBuilder;
use Tuxxedo\Database\Builder\SelectBuilderInterface;
use Tuxxedo\Database\Builder\UpdateBuilder;
use Tuxxedo\Database\Builder\UpdateBuilderInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

class MysqlConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;
    public readonly DialectInterface $dialect;

    private \mysqli $mysqli;
    private readonly \Closure $connector;
    private readonly StatementParserInterface $statementParser;

    private bool $inTransaction = false;

    private function __construct(
        private readonly ContainerInterface $container,
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
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

    public function select(
        string $table,
    ): SelectBuilderInterface {
        return new SelectBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function insert(
        string $table,
    ): InsertBuilderInterface {
        return new InsertBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function update(
        string $table,
    ): UpdateBuilderInterface {
        return new UpdateBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function delete(
        string $table,
    ): DeleteBuilderInterface {
        return new DeleteBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function exists(
        string $table,
    ): ExistsBuilder {
        return new ExistsBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function count(
        string $table,
    ): CountBuilderInterface {
        return new CountBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }
}
