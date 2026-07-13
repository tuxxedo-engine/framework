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

namespace Support\Database;

use Tuxxedo\Database\Config\ConnectionConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;

class StubConnectionConfig implements ConnectionConfigInterface
{
    /**
     * @param class-string<ConnectionInterface> $driverClass
     */
    public function __construct(
        public readonly string $name = 'stub',
        public readonly ConnectionRole $role = ConnectionRole::NONE,
        public readonly string $driverClass = StubConnection::class,
    ) {
    }
}
