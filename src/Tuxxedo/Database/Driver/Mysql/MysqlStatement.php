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

namespace Tuxxedo\Database\Driver\Mysql;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractStatement;
use Tuxxedo\Database\Driver\BindingInterface;
use Tuxxedo\Database\Driver\ParameterType;

class MysqlStatement extends AbstractStatement
{
    public function __construct(
        public readonly MysqlConnection $connection,
        public readonly string $sql,
    ) {
    }

    private function determineBindingType(
        BindingInterface $binding,
    ): string {
        return match ($binding->type) {
            ParameterType::STRING => 's',
            ParameterType::INT => 'i',
            ParameterType::FLOAT => 'f',
            ParameterType::BOOL => 'b',
            default => match (true) {
                \is_int($binding->value) => 'i',
                \is_float($binding->value) => 'f',
                \is_bool($binding->value) => 'b',
                default => 's',
            },
        };
    }

    public function execute(
        array $parameters = [],
    ): MysqlResultSet {
        $bindingTypes = '';
        $bindingValues = [];

        /** @var BindingInterface $binding */
        foreach ($this->bindings as $binding) {
            $bindingTypes = $this->determineBindingType($binding);
            $bindingValues[] = $binding->value;
        }

        $mysqli = $this->connection->getDriverInstance();

        $statement = $mysqli->prepare($this->sql);

        if ($statement === false) {
            throw DatabaseException::fromError(
                sqlState: $mysqli->sqlstate,
                code: $mysqli->errno,
                error: $mysqli->error,
            );
        }

        $statement->bind_param($bindingTypes, ...$bindingValues);

        if (!$statement->execute() || ($result = $statement->get_result()) === false) {
            throw DatabaseException::fromError(
                sqlState: $mysqli->sqlstate,
                code: $mysqli->errno,
                error: $mysqli->error,
            );
        }

        $affectedRows = $mysqli->affected_rows;

        if (\is_string($affectedRows)) {
            throw DatabaseException::fromValueOverflow(
                value: $affectedRows,
            );
        }

        if ($affectedRows < 0) {
            $affectedRows = 0;
        }

        return new MysqlResultSet(
            result: $result,
            affectedRows: $affectedRows,
        );
    }
}
