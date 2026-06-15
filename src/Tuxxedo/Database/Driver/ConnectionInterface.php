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
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;
use Tuxxedo\Database\Query\Builder\UpdateBuilderInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;
use Tuxxedo\Database\Query\Statement\CountStatementInterface;
use Tuxxedo\Database\Query\Statement\DeleteStatementInterface;
use Tuxxedo\Database\Query\Statement\ExistsStatementInterface;
use Tuxxedo\Database\Query\Statement\InsertBulkStatementInterface;
use Tuxxedo\Database\Query\Statement\InsertStatementInterface;
use Tuxxedo\Database\Query\Statement\Table\DropTableStatementInterface;
use Tuxxedo\Database\SqlException;

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

    public StatementParserInterface $statementParser {
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
     * @template TReturn
     *
     * @param \Closure(self $connection): TReturn $transaction
     * @return TReturn
     *
     * @throws DatabaseException
     */
    public function transaction(
        \Closure $transaction,
    ): mixed;

    /**
     * @template TReturn
     *
     * @param \Closure(self $connection): TReturn $transaction
     * @return TReturn
     *
     * @throws DatabaseException
     */
    public function nestedTransaction(
        \Closure $transaction,
    ): mixed;

    /**
     * @throws DatabaseException
     */
    #[\NoDiscard]
    public function savepoint(): string;

    /**
     * @throws DatabaseException
     */
    public function releaseSavepoint(
        string $name,
    ): void;

    /**
     * @throws DatabaseException
     */
    public function rollbackToSavepoint(
        string $name,
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
    ): InsertStatementInterface;

    public function insertBulk(
        string $table,
    ): InsertBulkStatementInterface;

    public function update(
        string $table,
    ): UpdateBuilderInterface;

    public function delete(
        string $table,
    ): DeleteStatementInterface;

    public function exists(
        string $table,
    ): ExistsStatementInterface;

    public function count(
        string $table,
    ): CountStatementInterface;

    // @todo createTable builder
    // @todo alterTable builder

    public function dropTable(
        string $table,
    ): DropTableStatementInterface;
}
