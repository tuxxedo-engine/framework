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

namespace Tuxxedo\Database\Driver\Pgsql;

use Tuxxedo\Database\Driver\AbstractStatement;
use Tuxxedo\Database\Driver\BindingInterface;
use Tuxxedo\Database\Driver\ParameterType;

class PgsqlStatement extends AbstractStatement
{
    public function __construct(
        public readonly PgsqlConnection $connection,
        public readonly string $sql,
    ) {
    }

    public function execute(
        array $parameters = [],
    ): PgsqlResultSet {
        $this->bindAll($parameters);

        $params = [];
        $pgsql = $this->connection->getDriverInstance();

        /** @var BindingInterface $binding */
        foreach ($this->bindings as $binding) {
            $params[] = match ($binding->type) {
                ParameterType::INT => (string) (int) $binding->value,
                ParameterType::FLOAT => (string) (float) $binding->value,
                ParameterType::BOOL => \boolval($binding->value)
                    ? 't'
                    : 'f',
                ParameterType::NULL => null,
                default => match (true) {
                    \is_int($binding->value) => (string) $binding->value,
                    \is_float($binding->value) => (string) $binding->value,
                    \is_bool($binding->value) => $binding->value
                        ? 't'
                        : 'f',
                    \is_null($binding->value) => null,
                    default => \strval($binding->value),
                },
            };
        }

        $result = \pg_query_params(
            $pgsql,
            $this->sql,
            $params,
        );

        if ($result === false) {
            $this->connection->throwFromLastError($pgsql);
        }

        return new PgsqlResultSet(
            result: $result,
            affectedRows: \pg_affected_rows($result),
        );
    }
}
