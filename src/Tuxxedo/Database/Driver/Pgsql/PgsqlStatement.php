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

namespace Tuxxedo\Database\Driver\Pgsql;

use Tuxxedo\Database\Driver\AbstractStatement;

class PgsqlStatement extends AbstractStatement
{
    public function __construct(
        public readonly PgsqlConnection $connection,
        public readonly string $sql,
    ) {
    }

    public function execute(
        array $parameters = [],
    ): PgsqlResultSet {
        // @todo Implement execute() method.
    }
}
