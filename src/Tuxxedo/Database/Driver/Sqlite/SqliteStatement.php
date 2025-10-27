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

use Tuxxedo\Database\Driver\AbstractStatement;
use Tuxxedo\Database\Driver\BindingInterface;
use Tuxxedo\Database\Driver\ParameterType;

class SqliteStatement extends AbstractStatement
{
    public function __construct(
        public readonly SqliteConnection $connection,
        public readonly string $sql,
    ) {
    }

    private function determineBindingType(
        BindingInterface $binding,
    ): int {
        return match ($binding->type) {
            ParameterType::STRING => \SQLITE3_TEXT,
            ParameterType::INT, ParameterType::BOOL => \SQLITE3_INTEGER,
            ParameterType::FLOAT => \SQLITE3_FLOAT,
            ParameterType::NULL => \SQLITE3_NULL,
            default => match (true) {
                \is_int($binding->value) || \is_bool($binding->value) => \SQLITE3_INTEGER,
                \is_float($binding->value) => \SQLITE3_FLOAT,
                \is_null($binding->value) => \SQLITE3_NULL,
                default => \SQLITE3_TEXT,
            },
        };
    }

    public function execute(
        array $parameters = [],
    ): SqliteResultSet {
        $this->bindAll($parameters);

        $sqlite = $this->connection->getDriverInstance();
        $statement = $sqlite->prepare($this->sql);

        if ($statement === false) {
            $this->connection->throwFromLastError($sqlite);
        }

        /** @var BindingInterface $binding */
        foreach ($this->bindings as $binding) {
            $bound = $statement->bindValue(
                param: $binding->name,
                value: $binding->value,
                type: $this->determineBindingType($binding),
            );

            if (!$bound) {
                $this->connection->throwFromLastError($sqlite);
            }
        }

        try {
            $result = $statement->execute();
        } catch (\SQLite3Exception $exception) {
            $this->connection->throwFromSqliteException($exception);
        }

        if ($result === false) {
            $this->connection->throwFromLastError($sqlite);
        }

        return new SqliteResultSet(
            result: $result,
            affectedRows: $sqlite->changes(),
        );
    }
}
