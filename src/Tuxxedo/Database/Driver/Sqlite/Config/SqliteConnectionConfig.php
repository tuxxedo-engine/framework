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

namespace Tuxxedo\Database\Driver\Sqlite\Config;

use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\Sqlite\SqliteConnection;

class SqliteConnectionConfig implements SqliteConnectionConfigInterface
{
    public private(set) string $driverClass = SqliteConnection::class;

    public function __construct(
        public readonly string $name = '',
        public readonly ConnectionRole $role = ConnectionRole::DEFAULT,
        public readonly string $database = '',
        #[\SensitiveParameter] public readonly string $encryptionKey = '',
        public readonly ?int $flags = null,
        public readonly bool $lazy = true,
    ) {
    }
}
