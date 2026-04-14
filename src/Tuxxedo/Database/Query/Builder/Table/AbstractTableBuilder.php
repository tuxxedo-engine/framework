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

namespace Tuxxedo\Database\Query\Builder\Table;

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Query\Builder\BuilderInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResult;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;

abstract class AbstractTableBuilder implements BuilderInterface
{
    /**
     * @var array<string|int|float|bool|null|array<string|int|float|bool|null>>
     */
    protected array $parameters = [];

    final public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly string $table,
    ) {
    }

    abstract protected function generateSql(): string;

    public function compile(): StatementParserResultInterface
    {
        return new StatementParserResult(
            sql: $this->generateSql(),
        );
    }

    public function execute(): ResultSetInterface
    {
        return $this->connection->query(
            sql: $this->generateSql(),
            native: true,
        );
    }
}
