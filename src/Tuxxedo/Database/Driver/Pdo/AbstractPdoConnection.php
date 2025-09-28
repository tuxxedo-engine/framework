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

namespace Tuxxedo\Database\Driver\Pdo;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

abstract class AbstractPdoConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver|string $driver;

    public function __construct(
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = static::getDriverName();
    }

    abstract protected function getDriverName(): DefaultDriver|string;

    abstract protected function getDsn(
        ConfigInterface $config,
    ): string;

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
        // @todo Implement transaction() method.
    }

    public function prepare(
        string $sql,
    ): PdoStatement {
        // @todo Implement prepare() method.
    }

    public function execute(
        string $sql,
        array $parameters = [],
    ): PdoResultSet {
        // @todo Implement execute() method.
    }

    public function query(
        string $sql,
    ): PdoResultSet {
        // @todo Implement query() method.
    }
}
