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

class ListDatabasesStatement implements ListDatabasesStatementInterface
{
    public function __construct(
        public readonly ?ConnectionInterface $connection = null,
    ) {
    }

    public function compile(
        ?ConnectionInterface $connection = null,
    ): StatementParserResultInterface {
        $resolvedConnection = $this->resolveConnection($connection);

        return $resolvedConnection->dialect->listDatabases();
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $this->resolveConnection($connection);
        $prepared = $resolvedConnection->dialect->listDatabases();

        return $resolvedConnection->query(
            sql: $prepared->sql,
            parameters: $prepared->parameters,
        );
    }

    public function all(
        ?ConnectionInterface $connection = null,
    ): array {
        $result = $this->execute(
            connection: $connection,
        );

        $names = [];
        $rowCount = \sizeof($result);

        for ($i = 0; $i < $rowCount; $i++) {
            /** @var string $name */
            $name = $result->fetchRow()[0];
            $names[] = $name;
        }

        return $names;
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
