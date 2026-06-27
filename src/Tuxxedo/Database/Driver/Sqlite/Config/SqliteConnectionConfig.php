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

readonly class SqliteConnectionConfig implements SqliteConnectionConfigInterface
{
    public function __construct(
        public string $name = '',
        public ConnectionRole $role = ConnectionRole::DEFAULT,
        public string $database = '',
        #[\SensitiveParameter] public string $encryptionKey = '',
        public ?int $flags = null,
        public bool $lazy = true,
    ) {
    }
}
