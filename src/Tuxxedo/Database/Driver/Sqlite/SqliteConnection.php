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
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

class SqliteConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;

    public function __construct(
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = DefaultDriver::SQLITE;
    }

    public function getDriverInstance(): object
    {
        // @todo Implement getDriverInstance() method.
    }

    public function connect(
        bool $reconnect = false,
    ): void {
        // @todo Implement connect() method.
    }

    public function close(): void
    {
        // @todo Implement close() method.
    }

    public function isConnected(): bool
    {
        // @todo Implement isConnected() method.
    }

    public function ping(): bool
    {
        // @todo Implement ping() method.
    }

    public function serverVersion(): string
    {
        // @todo Implement serverVersion() method.
    }

    public function escape(
        string $value,
    ): string {
        // @todo Implement escape() method.
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
    ): SqliteStatement {
        // @todo Implement prepare() method.
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
        // @todo Implement query() method.
    }
}
