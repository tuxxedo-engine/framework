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

namespace Tuxxedo\Database\Driver\Mysql\Config;

use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\Mysql\MysqlConnection;

class MysqlConnectionConfig implements MysqlConnectionConfigInterface
{
    public private(set) string $driverClass = MysqlConnection::class;

    public function __construct(
        public readonly string $name = '',
        public readonly ConnectionRole $role = ConnectionRole::DEFAULT,
        public readonly string $host = 'localhost',
        public readonly ?int $port = null,
        public readonly ?string $unixSocket = null,
        public readonly string $username = '',
        #[\SensitiveParameter] public readonly string $password = '',
        public readonly ?string $database = null,
        public readonly string $charset = 'utf8mb4',
        public readonly bool $persistent = false,
        public readonly bool $lazy = true,
        public readonly ?int $timeout = null,
        public readonly ?int $flags = null,
        public readonly bool $sslEnabled = false,
        public readonly string $sslCa = '',
        public readonly string $sslCert = '',
        public readonly string $sslKey = '',
        public readonly bool $sslVerifyPeer = true,
    ) {
    }
}
