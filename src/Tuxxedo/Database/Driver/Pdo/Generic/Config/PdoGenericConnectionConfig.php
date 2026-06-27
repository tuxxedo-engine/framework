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

readonly class PdoGenericConnectionConfig implements PdoGenericConnectionConfigInterface
{
    public function __construct(
        public string $name = '',
        public ConnectionRole $role = ConnectionRole::DEFAULT,
        public string $dsn = '',
        public string $username = '',
        #[\SensitiveParameter] public string $password = '',
        public bool $persistent = false,
        public bool $lazy = true,
    ) {
    }
}
