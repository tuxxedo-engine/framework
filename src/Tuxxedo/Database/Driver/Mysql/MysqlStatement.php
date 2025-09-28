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

namespace Tuxxedo\Database\Driver\Mysql;

use Tuxxedo\Database\Driver\AbstractStatement;

class MysqlStatement extends AbstractStatement
{
    public function __construct(
        public readonly MysqlConnection $connection,
        public readonly string $sql,
    ) {
    }

    public function execute(
        array $parameters = [],
    ): MysqlResultSet {
        // @todo Implement execute() method.
    }
}
