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

namespace Support\Database;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Config\ConnectionConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;
use Tuxxedo\Database\Query\Statement\CountStatementInterface;
use Tuxxedo\Database\Query\Statement\DeleteStatementInterface;
use Tuxxedo\Database\Query\Statement\ExistsStatementInterface;
use Tuxxedo\Database\Query\Statement\InsertBulkStatementInterface;
use Tuxxedo\Database\Query\Statement\InsertStatementInterface;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\Table\AlterTableStatementInterface;
use Tuxxedo\Database\Query\Statement\Table\CreateTableStatementInterface;
use Tuxxedo\Database\Query\Statement\Table\DropTableStatementInterface;
use Tuxxedo\Database\Query\Statement\UpdateStatementInterface;

class StubConnection implements ConnectionInterface
{
    public DialectInterface $dialect {
        get {
            if ($this->dialectImpl !== null) {
                return $this->dialectImpl;
            }

            throw new \LogicException('StubConnection: dialect not implemented');
        }
    }

    public StatementParserInterface $statementParser {
        get {
            throw new \LogicException('StubConnection: statementParser not implemented');
        }
    }

    /**
     * @var list<string>
     */
    public array $recordedQueries = [];

    /**
     * @param (\Closure(string $sql): ResultSetInterface)|null $queryHandler
     */
    public function __construct(
        public readonly string $name = 'stub',
        public readonly ConnectionRole $role = ConnectionRole::NONE,
        private readonly ?DialectInterface $dialectImpl = null,
        private readonly ?\Closure $queryHandler = null,
    ) {
    }

    public static function create(
        ContainerInterface $container,
        ConnectionConfigInterface $config,
    ): self {
        return new self(
            name: $config->name,
            role: $config->role,
        );
    }

    public function getDriverInstance(): object
    {
        throw new \LogicException('StubConnection: getDriverInstance not implemented');
    }

    public function connect(
        bool $reconnect = false,
    ): void {
        throw new \LogicException('StubConnection: connect not implemented');
    }

    public function close(): void
    {
        throw new \LogicException('StubConnection: close not implemented');
    }

    public function isConnected(): bool
    {
        return false;
    }

    public function ping(): bool
    {
        return false;
    }

    public function serverVersion(): string
    {
        throw new \LogicException('StubConnection: serverVersion not implemented');
    }

    public function lastInsertIdAsString(): ?string
    {
        throw new \LogicException('StubConnection: lastInsertIdAsString not implemented');
    }

    public function lastInsertIdAsInt(): ?int
    {
        throw new \LogicException('StubConnection: lastInsertIdAsInt not implemented');
    }

    public function begin(): void
    {
        throw new \LogicException('StubConnection: begin not implemented');
    }

    public function commit(): void
    {
        throw new \LogicException('StubConnection: commit not implemented');
    }

    public function rollback(): void
    {
        throw new \LogicException('StubConnection: rollback not implemented');
    }

    public function inTransaction(): bool
    {
        return false;
    }

    public function transaction(
        \Closure $transaction,
    ): mixed {
        throw new \LogicException('StubConnection: transaction not implemented');
    }

    public function nestedTransaction(
        \Closure $transaction,
    ): mixed {
        throw new \LogicException('StubConnection: nestedTransaction not implemented');
    }

    #[\NoDiscard]
    public function savepoint(): string
    {
        throw new \LogicException('StubConnection: savepoint not implemented');
    }

    public function releaseSavepoint(
        string $name,
    ): void {
        throw new \LogicException('StubConnection: releaseSavepoint not implemented');
    }

    public function rollbackToSavepoint(
        string $name,
    ): void {
        throw new \LogicException('StubConnection: rollbackToSavepoint not implemented');
    }

    public function query(
        string $sql,
        array $parameters = [],
        bool $native = false,
    ): ResultSetInterface {
        if ($this->queryHandler !== null) {
            $this->recordedQueries[] = $sql;

            return ($this->queryHandler)($sql);
        }

        throw new \LogicException('StubConnection: query not implemented');
    }

    public function select(
        string $table,
    ): SelectStatementInterface {
        throw new \LogicException('StubConnection: select not implemented');
    }

    public function insert(
        string $table,
    ): InsertStatementInterface {
        throw new \LogicException('StubConnection: insert not implemented');
    }

    public function insertBulk(
        string $table,
    ): InsertBulkStatementInterface {
        throw new \LogicException('StubConnection: insertBulk not implemented');
    }

    public function update(
        string $table,
    ): UpdateStatementInterface {
        throw new \LogicException('StubConnection: update not implemented');
    }

    public function delete(
        string $table,
    ): DeleteStatementInterface {
        throw new \LogicException('StubConnection: delete not implemented');
    }

    public function exists(
        string $table,
    ): ExistsStatementInterface {
        throw new \LogicException('StubConnection: exists not implemented');
    }

    public function count(
        string $table,
    ): CountStatementInterface {
        throw new \LogicException('StubConnection: count not implemented');
    }

    public function createTable(
        string $table,
    ): CreateTableStatementInterface {
        throw new \LogicException('StubConnection: createTable not implemented');
    }

    public function alterTable(
        string $table,
    ): AlterTableStatementInterface {
        throw new \LogicException('StubConnection: alterTable not implemented');
    }

    public function dropTable(
        string $table,
    ): DropTableStatementInterface {
        throw new \LogicException('StubConnection: dropTable not implemented');
    }
}
