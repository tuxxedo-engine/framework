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

namespace Tuxxedo\Database\Driver\Sqlite;

use Tuxxedo\Database\Driver\AbstractStatement;

class SqliteStatement extends AbstractStatement
{
    public function __construct(
        public readonly SqliteConnection $connection,
        public readonly string $sql,
    ) {
    }

    public function execute(
        array $parameters = [],
    ): SqliteResultSet {
        // @todo Implement execute() method.
    }
}
