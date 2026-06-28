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

namespace Tuxxedo\Database\Driver\Pdo\Pgsql\Config;

use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\Pdo\Pgsql\PdoPgsqlConnection;

class PdoPgsqlConnectionConfig implements PdoPgsqlConnectionConfigInterface
{
    public private(set) string $driverClass = PdoPgsqlConnection::class;

    public function __construct(
        public readonly string $name = '',
        public readonly ConnectionRole $role = ConnectionRole::DEFAULT,
        public readonly string $dsn = '',
        public readonly string $host = 'localhost',
        public readonly ?int $port = null,
        public readonly string $username = '',
        #[\SensitiveParameter] public readonly string $password = '',
        public readonly string $database = '',
        public readonly string $charset = 'UTF8',
        public readonly bool $persistent = false,
        public readonly bool $lazy = true,
        public readonly ?int $timeout = null,
        public readonly bool $sslEnabled = false,
        public readonly string $sslMode = '',
        public readonly string $sslCa = '',
        public readonly string $sslCert = '',
        public readonly string $sslKey = '',
    ) {
    }
}
