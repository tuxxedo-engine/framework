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

namespace Tuxxedo\Database\Driver\Pdo;

use Tuxxedo\Database\Driver\AbstractStatement;
use Tuxxedo\Database\Driver\BindingInterface;
use Tuxxedo\Database\Driver\ParameterType;

class PdoStatement extends AbstractStatement
{
    public function __construct(
        public readonly AbstractPdoConnection $connection,
        public readonly string $sql,
    ) {
    }

    private function determineBindingType(
        BindingInterface $binding,
    ): int {
        return match ($binding->type) {
            ParameterType::STRING => \PDO::PARAM_STR,
            ParameterType::INT => \PDO::PARAM_INT,
            ParameterType::FLOAT => \PDO::PARAM_STR,
            ParameterType::BOOL => \PDO::PARAM_BOOL,
            ParameterType::NULL => \PDO::PARAM_NULL,
            default => match (true) {
                \is_int($binding->value) => \PDO::PARAM_INT,
                \is_bool($binding->value) => \PDO::PARAM_BOOL,
                \is_null($binding->value) => \PDO::PARAM_NULL,
                default => \PDO::PARAM_STR,
            },
        };
    }

    public function execute(
        array $parameters = [],
    ): PdoResultSet {
        $this->bindAll($parameters);

        $statement = $this->connection->getDriverInstance()->prepare($this->sql);

        if ($statement === false) {
            $this->connection->throwFromErrorInfo();
        }

        /** @var BindingInterface $binding */
        foreach ($this->bindings as $binding) {
            $bound = $statement->bindValue(
                param: $binding->name,
                value: $binding->value,
                type: $this->determineBindingType($binding),
            );

            if (!$bound) {
                $this->connection->throwFromErrorInfo(
                    statement: $statement,
                );
            }
        }

        if (!$statement->execute()) {
            $this->connection->throwFromErrorInfo(
                statement: $statement,
            );
        }

        if ($statement->columnCount() > 0) {
            return new PdoResultSet(
                result: $statement,
                affectedRows: 0,
            );
        }

        return new PdoResultSet(
            result: null,
            affectedRows: $statement->rowCount(),
        );
    }
}
