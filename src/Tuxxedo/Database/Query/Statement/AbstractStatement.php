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

namespace Tuxxedo\Database\Query\Statement;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;

abstract class AbstractStatement implements StatementInterface
{
    /**
     * @var array<string|int|float|bool|null|array<string|int|float|bool|null>>
     */
    protected array $parameters = [];

    final public function __construct(
        public readonly string $table,
        public readonly ?ConnectionInterface $connection = null,
    ) {
    }

    abstract protected function generateSql(
        DialectInterface $dialect,
    ): string;

    public function compile(
        ?ConnectionInterface $connection = null,
    ): StatementParserResultInterface {
        $resolvedConnection = $connection ?? $this->connection;

        if ($resolvedConnection === null) {
            throw DatabaseException::fromNoConnectionAvailable();
        }

        return $resolvedConnection->statementParser->parse(
            sql: $this->generateSql($resolvedConnection->dialect),
            parameters: $this->parameters,
        );
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $connection ?? $this->connection;

        if ($resolvedConnection === null) {
            throw DatabaseException::fromNoConnectionAvailable();
        }

        $statement = $this->compile($resolvedConnection);

        return $resolvedConnection->query(
            sql: $statement->sql,
            parameters: \sizeof($statement->parameters) > 0
                ? \array_combine(\range(1, \sizeof($statement->parameters)), $statement->parameters)
                : [],
            native: true,
        );
    }
}
