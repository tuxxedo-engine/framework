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

namespace Tuxxedo\Database\Driver\Sqlite;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Query\Builder\CountBuilder;
use Tuxxedo\Database\Query\Builder\CountBuilderInterface;
use Tuxxedo\Database\Query\Builder\DeleteBuilder;
use Tuxxedo\Database\Query\Builder\DeleteBuilderInterface;
use Tuxxedo\Database\Query\Builder\ExistsBuilder;
use Tuxxedo\Database\Query\Builder\InsertBuilder;
use Tuxxedo\Database\Query\Builder\InsertBuilderInterface;
use Tuxxedo\Database\Query\Builder\InsertBulkBuilder;
use Tuxxedo\Database\Query\Builder\InsertBulkBuilderInterface;
use Tuxxedo\Database\Query\Builder\SelectBuilder;
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;
use Tuxxedo\Database\Query\Builder\UpdateBuilder;
use Tuxxedo\Database\Query\Builder\UpdateBuilderInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\SqliteDialect;
use Tuxxedo\Database\Query\Parser\StatementParser;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;

class SqliteConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;
    public readonly DialectInterface $dialect;

    private \SQLite3 $sqlite;
    private readonly \Closure $connector;
    private readonly StatementParserInterface $statementParser;

    private bool $inTransaction = false;

    private function __construct(
        private readonly ContainerInterface $container,
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = DefaultDriver::SQLITE;
        $this->dialect = new SqliteDialect();
        $this->statementParser = new StatementParser(
            dialect: $this->dialect,
        );

        $this->connector = function () use ($config): void {
            try {
                $this->sqlite = new \SQLite3(
                    filename: $config->getString('database'),
                    flags: $config->isInt('options.flags')
                        ? $config->getInt('options.flags')
                        : \SQLITE3_OPEN_READWRITE | \SQLITE3_OPEN_CREATE,
                    encryptionKey: $config->getString('password'),
                );

                $this->sqlite->enableExceptions(true);
                $this->sqlite->enableExtendedResultCodes(true);
            } catch (\Exception $exception) {
                throw DatabaseException::fromCannotConnect(
                    code: $exception->getCode(),
                    error: $exception->getMessage(),
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

    private function connectCheck(): void
    {
        if (!isset($this->sqlite)) {
            $this->connect();
        }
    }

    public function throwFromSqliteException(
        \SQLite3Exception $exception,
    ): never {
        throw DatabaseException::fromError(
            sqlState: 'HY000',
            code: $exception->getCode(),
            error: $exception->getMessage(),
        );
    }

    public function throwFromLastError(
        \SQLite3 $sqlite,
    ): never {
        throw DatabaseException::fromError(
            sqlState: 'HY000',
            code: $sqlite->lastErrorCode(),
            error: $sqlite->lastErrorMsg(),
        );
    }

    public function getDriverInstance(): \SQLite3
    {
        $this->connectCheck();

        return $this->sqlite;
    }

    public function connect(
        bool $reconnect = false,
    ): void {
        if ($reconnect || !isset($this->sqlite)) {
            ($this->connector)();
        }
    }

    public function close(): void
    {
        if (isset($this->sqlite)) {
            $this->sqlite->close();

            unset($this->sqlite);
        }
    }

    public function isConnected(): bool
    {
        return isset($this->sqlite);
    }

    public function ping(): bool
    {
        try {
            $this->connectCheck();

            $this->sqlite->query('SELECT 1');

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function serverVersion(): string
    {
        /** @var string */
        return \SQLite3::version()['versionString'];
    }

    public function lastInsertIdAsString(): ?string
    {
        $this->connectCheck();

        return ($id = $this->lastInsertIdAsInt()) !== null
            ? (string) $id
            : null;
    }

    public function lastInsertIdAsInt(): ?int
    {
        $this->connectCheck();

        $id = $this->sqlite->lastInsertRowID();

        if ($id !== 0) {
            return $id;
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
            if ($this->sqlite->exec('BEGIN IMMEDIATE') === false) {
                $this->throwFromLastError($this->sqlite);
            }

            $this->inTransaction = true;
        } catch (\SQLite3Exception $exception) {
            $this->throwFromSqliteException($exception);
        }
    }

    public function commit(): void
    {
        $this->connectCheck();

        if (!$this->inTransaction) {
            throw DatabaseException::fromNotInTransaction();
        }

        try {
            if ($this->sqlite->exec('COMMIT') === false) {
                $this->throwFromLastError($this->sqlite);
            }
        } catch (\SQLite3Exception $exception) {
            $this->throwFromSqliteException($exception);
        } finally {
            $this->inTransaction = false;
        }
    }

    public function rollback(): void
    {
        $this->connectCheck();

        if (!$this->inTransaction) {
            throw DatabaseException::fromNotInTransaction();
        }

        try {
            if ($this->sqlite->exec('ROLLBACK') === false) {
                $this->throwFromLastError($this->sqlite);
            }
        } catch (\SQLite3Exception $exception) {
            $this->throwFromSqliteException($exception);
        } finally {
            $this->inTransaction = false;
        }
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
    ): SqliteResultSet {
        $this->connectCheck();

        if (!$native) {
            $parsedStatement = $this->statementParser->parse($sql, $parameters);
            $sql = $parsedStatement->sql;
            $parameters = $parsedStatement->parameters;
        }

        $statement = $this->sqlite->prepare($sql);

        if ($statement === false) {
            $this->throwFromLastError($this->sqlite);
        }

        foreach ($parameters as $index => $value) {
            if (\is_array($value)) {
                continue;
            }

            $bound = $statement->bindValue(
                param: !$native
                    ? $index + 1
                    : $index,
                value: $value,
                type: match (true) {
                    \is_int($value) || \is_bool($value) => \SQLITE3_INTEGER,
                    \is_float($value) => \SQLITE3_FLOAT,
                    \is_null($value) => \SQLITE3_NULL,
                    default => \SQLITE3_TEXT,
                },
            );

            if (!$bound) {
                $this->throwFromLastError($this->sqlite);
            }
        }

        try {
            $result = $statement->execute();
        } catch (\SQLite3Exception $exception) {
            $this->throwFromSqliteException($exception);
        }

        if ($result === false) {
            $this->throwFromLastError($this->sqlite);
        }

        return new SqliteResultSet(
            container: $this->container,
            result: $result,
            affectedRows: $this->sqlite->changes(),
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

    public function insertBulk(
        string $table,
    ): InsertBulkBuilderInterface {
        return new InsertBulkBuilder(
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
