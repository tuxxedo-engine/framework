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

readonly class MysqlConnectionConfig implements MysqlConnectionConfigInterface
{
    public function __construct(
        public string $name = '',
        public ConnectionRole $role = ConnectionRole::DEFAULT,
        public string $host = 'localhost',
        public ?int $port = null,
        public ?string $unixSocket = null,
        public string $username = '',
        #[\SensitiveParameter] public string $password = '',
        public ?string $database = null,
        public string $charset = 'utf8mb4',
        public bool $persistent = false,
        public bool $lazy = true,
        public ?int $timeout = null,
        public ?int $flags = null,
        public bool $sslEnabled = false,
        public string $sslCa = '',
        public string $sslCert = '',
        public string $sslKey = '',
        public bool $sslVerifyPeer = true,
    ) {
    }
}
