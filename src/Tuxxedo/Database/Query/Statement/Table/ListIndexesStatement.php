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

class ListIndexesStatement implements ListIndexesStatementInterface
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

        return $resolvedConnection->dialect->listIndexes(
            table: $this->table,
        );
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $this->resolveConnection($connection);
        $prepared = $resolvedConnection->dialect->listIndexes(
            table: $this->table,
        );

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

        /**
         * @var array<string, array{unique: bool, primary: bool, columns: list<string>}> $accumulator
         */
        $accumulator = [];
        $rowCount = \sizeof($result);

        for ($i = 0; $i < $rowCount; $i++) {
            /** @var array{index_name: string, column_name: string, is_unique: int|string|bool, is_primary: int|string|bool} $row */
            $row = $result->fetchAssoc();

            $indexName = $row['index_name'];

            if (!\array_key_exists($indexName, $accumulator)) {
                $accumulator[$indexName] = [
                    'unique' => (bool) (int) $row['is_unique'],
                    'primary' => (bool) (int) $row['is_primary'],
                    'columns' => [],
                ];
            }

            $accumulator[$indexName]['columns'][] = $row['column_name'];
        }

        $indexes = [];

        foreach ($accumulator as $name => $data) {
            $indexes[] = new IndexMetadata(
                name: $name,
                columns: $data['columns'],
                unique: $data['unique'],
                primary: $data['primary'],
            );
        }

        return $indexes;
    }

    public function byName(
        ?ConnectionInterface $connection = null,
    ): array {
        $indexed = [];

        foreach ($this->all($connection) as $index) {
            $indexed[$index->name] = $index;
        }

        return $indexed;
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
