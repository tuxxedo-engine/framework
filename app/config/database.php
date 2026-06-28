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

use Tuxxedo\Database\Config\ConnectionManagerConfig;
use Tuxxedo\Database\Config\ConnectionManagerConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\Pdo\Mysql\Config\PdoMysqlConnectionConfig;

return static fn (): ConnectionManagerConfigInterface => new ConnectionManagerConfig(
    connections: [
        new PdoMysqlConnectionConfig(
            role: ConnectionRole::DEFAULT,
            host: 'localhost',
            port: 3306,
            username: 'root',
            password: '',
            database: 'tuxxedo',
            charset: 'utf8mb4',
            persistent: false,
            lazy: true,
            timeout: 3,
        ),
    ],
);
