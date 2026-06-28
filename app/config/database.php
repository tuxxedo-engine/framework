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
use Tuxxedo\Env\EnvInterface;

return static fn (EnvInterface $env): ConnectionManagerConfigInterface => new ConnectionManagerConfig(
    connections: [
        new PdoMysqlConnectionConfig(
            role: ConnectionRole::DEFAULT,
            host: $env->string('DB_HOST', 'localhost'),
            port: $env->int('DB_PORT', 3306),
            username: $env->string('DB_USER', 'root'),
            password: $env->string('DB_PASS', ''),
            database: $env->string('DB_NAME', 'tuxxedo'),
            charset: $env->string('DB_CHARSET', 'utf8mb4'),
            persistent: $env->bool('DB_PERSISTENT', false),
            lazy: $env->bool('DB_LAZY', true),
            timeout: $env->int('DB_TIMEOUT', 3),
        ),
    ],
);
