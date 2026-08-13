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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Config\ConnectionConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractConnection;
use Tuxxedo\Database\Driver\Sqlite\Config\SqliteConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\SqliteDialect;
use Tuxxedo\Database\Query\Parser\StatementParser;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;

class SqliteConnection extends AbstractConnection
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DialectInterface $dialect;

    private \SQLite3 $sqlite;
    private readonly \Closure $connector;
    private bool $inTransaction = false;

    public readonly StatementParserInterface $statementParser;

    private function __construct(
        private readonly ContainerInterface $container,
        SqliteConnectionConfigInterface $config,
    ) {
        $this->name = $config->name;
        $this->role = $config->role;
        $this->currentDatabase = $config->database;
        $this->dialect = new SqliteDialect();
        $this->statementParser = new StatementParser(
            dialect: $this->dialect,
        );

        $this->connector = function () use ($config): void {
            try {
                $this->sqlite = new \SQLite3(
                    filename: $config->database,
                    flags: $config->flags ?? (\SQLITE3_OPEN_READWRITE | \SQLITE3_OPEN_CREATE),
                    encryptionKey: $config->encryptionKey,
                );

                $this->sqlite->enableExceptions(true);
                $this->sqlite->enableExtendedResultCodes(true);
            } catch (\Exception $exception) { // @codeCoverageIgnore
                // @codeCoverageIgnoreStart
                throw DatabaseException::fromCannotConnect(
                    code: $exception->getCode(),
                    error: $exception->getMessage(),
                );
                // @codeCoverageIgnoreEnd
            }
        };

        if (!$config->lazy) {
            $this->connect();
        }
    }

    public static function create(
        ContainerInterface $container,
        ConnectionConfigInterface $config,
    ): self {
        /** @var SqliteConnectionConfigInterface $config */

        return new self($container, $config);
    }

    private function connectCheck(): void
    {
        if (!isset($this->sqlite)) {
            $this->connect();
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function throwFromSqliteException(
        \SQLite3Exception $exception,
    ): never {
        throw DatabaseException::fromError(
            sqlState: 'HY000',
            code: $exception->getCode(),
            error: $exception->getMessage(),
        );
    }

    /**
     * @codeCoverageIgnore
     */
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
        } catch (\Exception) { // @codeCoverageIgnore
            return false; // @codeCoverageIgnore
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
                $this->throwFromLastError($this->sqlite); // @codeCoverageIgnore
            }

            $this->inTransaction = true;
        } catch (\SQLite3Exception $exception) { // @codeCoverageIgnore
            $this->throwFromSqliteException($exception); // @codeCoverageIgnore
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
        } catch (\SQLite3Exception $exception) { // @codeCoverageIgnore
            $this->throwFromSqliteException($exception); // @codeCoverageIgnore
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
        } catch (\SQLite3Exception $exception) { // @codeCoverageIgnore
            $this->throwFromSqliteException($exception); // @codeCoverageIgnore
        } finally {
            $this->inTransaction = false;
        }
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function isServerInTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function switchDatabase(
        string $database,
    ): void {
        throw DatabaseException::fromSwitchDatabaseUnsupported();
    }

    public function currentDatabase(): string
    {
        return $this->currentDatabase;
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

        try {
            $statement = $this->sqlite->prepare($sql);
        } catch (\SQLite3Exception $exception) {
            $this->throwFromSqliteException($exception);
        }

        if ($statement === false) {
            $this->throwFromLastError($this->sqlite); // @codeCoverageIgnore
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
                $this->throwFromLastError($this->sqlite); // @codeCoverageIgnore
            }
        }

        try {
            $result = $statement->execute();
        } catch (\SQLite3Exception $exception) {
            $this->throwFromSqliteException($exception);
        }

        if ($result === false) {
            $this->throwFromLastError($this->sqlite); // @codeCoverageIgnore
        }

        return new SqliteResultSet(
            container: $this->container,
            result: $result,
            affectedRows: $this->sqlite->changes(),
        );
    }
}
