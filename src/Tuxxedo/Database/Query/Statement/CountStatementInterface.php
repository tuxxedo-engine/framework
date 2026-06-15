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

interface CountStatementInterface extends WhereStatementInterface
{
    public function column(
        string $column = '*',
    ): static;

    public function distinct(): static;

    /**
     * @throws DatabaseException
     * @throws SqlException
     */
    public function count(
        ?ConnectionInterface $connection = null,
    ): int;
}
