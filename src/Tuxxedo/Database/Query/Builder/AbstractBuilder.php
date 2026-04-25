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

namespace Tuxxedo\Database\Query\Builder;

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;

abstract class AbstractBuilder implements BuilderInterface
{
    /**
     * @var array<string|int|float|bool|null|array<string|int|float|bool|null>>
     */
    protected array $parameters = [];

    final public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly string $table,
        protected readonly StatementParserInterface $statementParser,
    ) {
    }

    abstract protected function generateSql(): string;

    public function compile(): StatementParserResultInterface
    {
        return $this->statementParser->parse(
            sql: $this->generateSql(),
            parameters: $this->parameters,
        );
    }

    public function execute(): ResultSetInterface
    {
        $statement = $this->compile();

        return $this->connection->query(
            sql: $statement->sql,
            parameters: \sizeof($statement->parameters) > 0
                ? \array_combine(\range(1, \sizeof($statement->parameters)), $statement->parameters)
                : [],
            native: true,
        );
    }
}
