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

use Tuxxedo\Database\Driver\ConnectionInterface;

class Query
{
    public static function insert(
        string $table,
        ?ConnectionInterface $connection = null,
    ): InsertStatement {
        return new InsertStatement(
            table: $table,
            connection: $connection,
        );
    }
}
