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

namespace Tuxxedo\Database\Driver\Pdo\Generic\Config;

use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\Pdo\Generic\PdoGenericConnection;

class PdoGenericConnectionConfig implements PdoGenericConnectionConfigInterface
{
    public private(set) string $driverClass = PdoGenericConnection::class;

    public function __construct(
        public readonly string $name = '',
        public readonly ConnectionRole $role = ConnectionRole::DEFAULT,
        public readonly string $dsn = '',
        public readonly string $username = '',
        #[\SensitiveParameter] public readonly string $password = '',
        public readonly bool $persistent = false,
        public readonly bool $lazy = true,
    ) {
    }
}
