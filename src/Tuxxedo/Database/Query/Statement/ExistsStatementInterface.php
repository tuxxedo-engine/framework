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
use Tuxxedo\Database\SqlException;

interface ExistsStatementInterface extends WhereStatementInterface
{
    /**
     * @throws DatabaseException
     * @throws SqlException
     */
    public function exists(
        ?ConnectionInterface $connection = null,
    ): bool;
}
