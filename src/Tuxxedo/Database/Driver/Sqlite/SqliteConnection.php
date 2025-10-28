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

namespace Tuxxedo\Database\Driver\Sqlite;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

class SqliteConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;

    private \SQLite3 $sqlite;
    private readonly \Closure $connector;

    private bool $inTransaction = false;

    public function __construct(
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = DefaultDriver::SQLITE;

        $this->connector = function () use ($config): void {
            try {
                $this->sqlite = new \SQLite3(
                    filename: $config->getString('database'),
                    flags: !$config->isInt('options.flags')
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

    public function lastInsertIdAsString(
        ?string $sequence = null,
    ): ?string {
        $this->connectCheck();

        return (string) $this->sqlite->lastInsertRowID();
    }

    public function lastInsertIdAsInt(
        ?string $sequence = null,
    ): ?int {
        $this->connectCheck();

        return $this->sqlite->lastInsertRowID();
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

    public function prepare(
        string $sql,
    ): SqliteStatement {
        $this->connectCheck();

        return new SqliteStatement(
            connection: $this,
            sql: $sql,
        );
    }

    public function execute(
        string $sql,
        array $parameters = [],
    ): SqliteResultSet {
        return $this->prepare($sql)->execute($parameters);
    }

    public function query(
        string $sql,
    ): SqliteResultSet {
        $this->connectCheck();

        try {
            $result = $this->sqlite->query($sql);
        } catch (\SQLite3Exception $exception) {
            $this->throwFromSqliteException($exception);
        }

        if ($result === false) {
            $this->throwFromLastError($this->sqlite);
        }

        return new SqliteResultSet(
            result: $result,
            affectedRows: $this->sqlite->changes(),
        );
    }
}
