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

class DescribeTableStatement implements DescribeTableStatementInterface
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

        return $resolvedConnection->dialect->describeTable(
            table: $this->table,
        );
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $this->resolveConnection($connection);
        $prepared = $resolvedConnection->dialect->describeTable(
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
        $result = $this->execute($connection);

        $columns = [];
        $rowCount = \sizeof($result);

        for ($i = 0; $i < $rowCount; $i++) {
            /** @var array{name: string, native_type: string, nullable: int|string|bool, column_default: string|null, is_primary: int|string|bool, is_auto_increment: int|string|bool} $row */
            $row = $result->fetchAssoc();

            $columns[] = new ColumnDescription(
                name: $row['name'],
                nativeType: $row['native_type'],
                nullable: (bool) (int) $row['nullable'],
                default: $row['column_default'],
                primary: (bool) (int) $row['is_primary'],
                autoIncrement: (bool) (int) $row['is_auto_increment'],
            );
        }

        return $columns;
    }

    public function byName(
        ?ConnectionInterface $connection = null,
    ): array {
        $indexed = [];

        foreach ($this->all($connection) as $column) {
            $indexed[$column->name] = $column;
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
