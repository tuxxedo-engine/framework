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

namespace Tuxxedo\Database\Builder;

use Tuxxedo\Database\Builder\Parser\StatementParserInterface;
use Tuxxedo\Database\Builder\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;

abstract class AbstractBuilder implements BuilderInterface
{
    final public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly string $table,
        protected readonly StatementParserInterface $statementParser,
    ) {
    }

    protected function generateSql(): string
    {
        // @todo Implement
        return '';
    }

    public function compile(): StatementParserResultInterface
    {
        return $this->statementParser->parse($this->generateSql());
    }

    public function execute(): ResultSetInterface
    {
        $statement = $this->compile();

        return $this->connection->query(
            sql: $statement->sql,
            parameters: $statement->parameters,
        );
    }
}
