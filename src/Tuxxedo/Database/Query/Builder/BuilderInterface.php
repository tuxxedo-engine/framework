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

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\SqlException;

interface BuilderInterface
{
    public ConnectionInterface $connection {
        get;
    }

    public string $table {
        get;
    }

    /**
     * @throws SqlException
     */
    public function compile(): StatementParserResultInterface;

    /**
     * @throws DatabaseException
     * @throws SqlException
     */
    public function execute(): ResultSetInterface;
}
