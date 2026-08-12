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

namespace Tuxxedo\Database\Query\Statement\Table;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;

class TableExistsStatement implements TableExistsStatementInterface
{
    public function __construct(
        public readonly string $table,
        public readonly ?ConnectionInterface $connection = null,
    ) {
    }

    public function compile(
        ?ConnectionInterface $connection = null,
    ): StatementParserResultInterface {
        $resolvedConnection = $this->resolveConnection($connection);

        return $resolvedConnection->dialect->tableExists(
            table: $this->table,
        );
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $this->resolveConnection($connection);
        $prepared = $resolvedConnection->dialect->tableExists(
            table: $this->table,
        );

        return $resolvedConnection->query(
            sql: $prepared->sql,
            parameters: $prepared->parameters,
        );
    }

    public function exists(
        ?ConnectionInterface $connection = null,
    ): bool {
        $resolvedConnection = $this->resolveConnection($connection);
        $result = $this->execute(
            connection: $resolvedConnection,
        );

        return $resolvedConnection->dialect->interpretBoolean(
            $result->fetchRow()[0],
        );
    }

    /**
     * @throws DatabaseException
     */
    private function resolveConnection(
        ?ConnectionInterface $connection,
    ): ConnectionInterface {
        $resolved = $connection ?? $this->connection;

        if ($resolved === null) {
            throw DatabaseException::fromNoConnectionAvailable();
        }

        return $resolved;
    }
}
