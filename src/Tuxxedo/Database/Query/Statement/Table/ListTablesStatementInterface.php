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
use Tuxxedo\Database\SqlException;

interface ListTablesStatementInterface
{
    public ?ConnectionInterface $connection {
        get;
    }

    /**
     * @throws DatabaseException
     * @throws SqlException
     */
    public function compile(
        ?ConnectionInterface $connection = null,
    ): StatementParserResultInterface;

    /**
     * @throws DatabaseException
     * @throws SqlException
     */
    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface;

    /**
     * @return list<string>
     *
     * @throws DatabaseException
     * @throws SqlException
     */
    public function all(
        ?ConnectionInterface $connection = null,
    ): array;
}
