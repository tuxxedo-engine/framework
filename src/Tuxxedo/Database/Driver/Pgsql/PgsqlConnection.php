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

namespace Tuxxedo\Database\Driver\Pgsql;

use PgSql\Connection;
use PgSql\Result;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

class PgsqlConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;

    private Connection $pgsql;
    private readonly \Closure $connector;

    public function __construct(
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = DefaultDriver::PGSQL;
    }

    private function connectCheck(): void
    {
        if (!isset($this->pgsql)) {
            $this->connect();
        }
    }

    public function throwFromLastError(
        Connection $pgsql,
    ): never {
        // @todo Implement
    }

    public function throwFromResult(
        Result $result,
    ): never {
        // @todo Implement
    }

    public function getDriverInstance(): Connection
    {
        $this->connectCheck();

        return $this->pgsql;
    }

    public function connect(
        bool $reconnect = false,
    ): void {
        if ($reconnect || !isset($this->pgsql)) {
            ($this->connector)();
        }
    }

    public function close(): void
    {
        if (isset($this->pgsql)) {
            @\pg_close($this->pgsql);

            unset($this->pgsql);
        }
    }

    public function isConnected(): bool
    {
        return isset($this->pgsql);
    }

    public function ping(): bool
    {
        try {
            $this->connectCheck();

            return \pg_query($this->pgsql, 'SELECT 1') !== false;
        } catch (\Exception) {
            return false;
        }
    }

    public function serverVersion(): string
    {
        // @todo Implement serverVersion() method.
    }

    public function lastInsertIdAsString(
        ?string $sequence = null,
    ): ?string {
        // @todo Implement lastInsertIdAsString() method.
    }

    public function lastInsertIdAsInt(
        ?string $sequence = null,
    ): ?int {
        // @todo Implement lastInsertIdAsInt() method.
    }

    public function begin(): void
    {
        // @todo Implement begin() method.
    }

    public function commit(): void
    {
        // @todo Implement commit() method.
    }

    public function rollback(): void
    {
        // @todo Implement rollback() method.
    }

    public function inTransaction(): bool
    {
        // @todo Implement inTransaction() method.
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
    ): PgsqlStatement {
        $this->connectCheck();

        return new PgsqlStatement(
            connection: $this,
            sql: $sql,
        );
    }

    public function execute(
        string $sql,
        array $parameters = [],
    ): PgsqlResultSet {
        return $this->prepare($sql)->execute($parameters);
    }

    public function query(
        string $sql,
    ): PgsqlResultSet {
        $this->connectCheck();

        $result = \pg_query($this->pgsql, $sql);

        if ($result === false) {
            $this->throwFromLastError($this->pgsql);
        }

        return new PgsqlResultSet(
            result: $result,
            affectedRows: \pg_affected_rows($result),
        );
    }
}
