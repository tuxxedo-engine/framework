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

namespace Tuxxedo\Database\Driver\Pdo\Sqlite\Config;

use Tuxxedo\Database\ConnectionRole;

readonly class PdoSqliteConnectionConfig implements PdoSqliteConnectionConfigInterface
{
    public function __construct(
        public string $name = '',
        public ConnectionRole $role = ConnectionRole::DEFAULT,
        public string $dsn = '',
        public string $database = '',
        public bool $persistent = false,
        public bool $lazy = true,
        public ?int $timeout = null,
    ) {
    }
}
