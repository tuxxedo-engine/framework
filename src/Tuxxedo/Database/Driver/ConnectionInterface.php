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

namespace Tuxxedo\Database\Driver;

use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;

// @todo Needs a more robust generalized escape mechanism
interface ConnectionInterface
{
    public string $name {
        get;
    }

    public ConnectionRole $role {
        get;
    }

    public DefaultDriver|string $driver {
        get;
    }

    public function getDriverInstance(): object;

    /**
     * @throws DatabaseException
     */
    public function connect(
        bool $reconnect = false,
    ): void;

    /**
     * @throws DatabaseException
     */
    public function close(): void;

    public function isConnected(): bool;

    public function ping(): bool;

    /**
     * @throws DatabaseException
     */
    public function serverVersion(): string;

    /**
     * @throws DatabaseException
     */
    public function lastInsertIdAsString(
        ?string $sequence = null,
    ): ?string;

    public function lastInsertIdAsInt(
        ?string $sequence = null,
    ): ?int;

    /**
     * @throws DatabaseException
     */
    public function begin(): void;

    /**
     * @throws DatabaseException
     */
    public function commit(): void;

    /**
     * @throws DatabaseException
     */
    public function rollback(): void;

    public function inTransaction(): bool;

    /**
     * @param \Closure(self $connection): void $transaction
     *
     * @throws DatabaseException
     */
    // @todo Support multiple transactions
    // @todo Support retries
    public function transaction(
        \Closure $transaction,
    ): void;

    public function prepare(
        string $sql,
    ): StatementInterface;

    /**
     * @param array<string|int|float|bool|null> $parameters
     *
     * @throws DatabaseException
     */
    public function execute(
        string $sql,
        array $parameters = [],
    ): ResultSetInterface;

    /**
     * @throws DatabaseException
     */
    public function query(
        string $sql,
    ): ResultSetInterface;
}
