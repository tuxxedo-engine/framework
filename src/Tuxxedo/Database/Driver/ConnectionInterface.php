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

namespace Tuxxedo\Database\Driver;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Builder\CountBuilderInterface;
use Tuxxedo\Database\Builder\DeleteBuilderInterface;
use Tuxxedo\Database\Builder\Dialect\DialectInterface;
use Tuxxedo\Database\Builder\ExistsBuilderInterface;
use Tuxxedo\Database\Builder\InsertBuilderInterface;
use Tuxxedo\Database\Builder\InsertBulkBuilderInterface;
use Tuxxedo\Database\Builder\SelectBuilderInterface;
use Tuxxedo\Database\Builder\UpdateBuilderInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\SqlException;

// @todo Support savepoints
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

    public DialectInterface $dialect {
        get;
    }

    public static function create(
        ContainerInterface $container,
        ConfigInterface $config,
    ): self;

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
    public function lastInsertIdAsString(): ?string;

    /**
     * @throws DatabaseException
     */
    public function lastInsertIdAsInt(): ?int;

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
    public function transaction(
        \Closure $transaction,
    ): void;

    /**
     * @param array<string|int|float|bool|null|array<string|int|float|bool|null>> $parameters
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    public function query(
        string $sql,
        array $parameters = [],
        bool $native = false,
    ): ResultSetInterface;

    public function select(
        string $table,
    ): SelectBuilderInterface;

    public function insert(
        string $table,
    ): InsertBuilderInterface;

    public function insertBulk(
        string $table,
    ): InsertBulkBuilderInterface;

    public function update(
        string $table,
    ): UpdateBuilderInterface;

    public function delete(
        string $table,
    ): DeleteBuilderInterface;

    public function exists(
        string $table,
    ): ExistsBuilderInterface;

    public function count(
        string $table,
    ): CountBuilderInterface;
}
