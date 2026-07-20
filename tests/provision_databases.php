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

use Support\Database\DatabaseServerProbe;
use Support\Database\MysqlTestEnv;
use Support\Database\PgsqlTestEnv;

require_once __DIR__ . '/../vendor/autoload.php';

$workerCount = (int) ($argv[1] ?? '0');

if ($workerCount < 1) {
    \fwrite(
        \STDERR,
        "[provision] Usage: php provision_databases.php <worker-count>\n",
    );

    exit(1);
}

$prefix = static function (string $envName, string $default): string {
    $value = \getenv($envName);

    return $value === false || $value === ''
        ? $default
        : $value;
};

if (DatabaseServerProbe::isMysqlAvailable()) {
    $mysqlPrefix = $prefix('TUXXEDO_TEST_MYSQL_DATABASE_PREFIX', 'tuxxedo_test');

    $mysqlAdmin = new mysqli(
        hostname: MysqlTestEnv::host(),
        username: MysqlTestEnv::username(),
        password: MysqlTestEnv::password(),
        database: MysqlTestEnv::adminDatabase(),
        port: MysqlTestEnv::port(),
    );

    if ($mysqlAdmin->connect_errno !== 0) {
        throw new RuntimeException(
            \sprintf(
                '[provision] MySQL admin connect failed: %s',
                $mysqlAdmin->connect_error ?? 'unknown error',
            ),
        );
    }

    for ($token = 1; $token <= $workerCount; $token++) {
        $name = \sprintf('%s_%d', $mysqlPrefix, $token);
        $escapedName = \str_replace('`', '``', $name);

        if ($mysqlAdmin->query(\sprintf('DROP DATABASE IF EXISTS `%s`', $escapedName)) === false) {
            throw new RuntimeException(
                \sprintf(
                    '[provision] MySQL DROP DATABASE `%s` failed: %s',
                    $name,
                    $mysqlAdmin->error,
                ),
            );
        }

        if ($mysqlAdmin->query(\sprintf('CREATE DATABASE `%s`', $escapedName)) === false) {
            throw new RuntimeException(
                \sprintf(
                    '[provision] MySQL CREATE DATABASE `%s` failed: %s',
                    $name,
                    $mysqlAdmin->error,
                ),
            );
        }

        \printf("[provision] MySQL: created %s\n", $name);
    }

    $mysqlAdmin->close();
}

if (DatabaseServerProbe::isPgsqlAvailable()) {
    $pgsqlPrefix = $prefix('TUXXEDO_TEST_PGSQL_DATABASE_PREFIX', 'tuxxedo_test');

    $pgsqlQuote = static function (string $value): string {
        return "'" . \addcslashes($value, "\\'") . "'";
    };

    $dsnParts = [
        'host=' . $pgsqlQuote(PgsqlTestEnv::host()),
        'sslmode=' . $pgsqlQuote('disable'),
    ];

    $port = PgsqlTestEnv::port();

    if ($port !== null) {
        $dsnParts[] = 'port=' . $pgsqlQuote((string) $port);
    }

    $user = PgsqlTestEnv::username();

    if ($user !== '') {
        $dsnParts[] = 'user=' . $pgsqlQuote($user);
    }

    $pass = PgsqlTestEnv::password();

    if ($pass !== '') {
        $dsnParts[] = 'password=' . $pgsqlQuote($pass);
    }

    $dsnParts[] = 'dbname=' . $pgsqlQuote(PgsqlTestEnv::adminDatabase());

    $pgsqlAdmin = @\pg_connect(\join(' ', $dsnParts));

    if (!$pgsqlAdmin instanceof PgSql\Connection) {
        $errorInfo = \error_get_last();

        throw new RuntimeException(
            \sprintf(
                '[provision] PgSQL admin connect failed: %s',
                $errorInfo['message'] ?? 'unknown error',
            ),
        );
    }

    for ($token = 1; $token <= $workerCount; $token++) {
        $name = \sprintf('%s_%d', $pgsqlPrefix, $token);
        $escapedName = \str_replace('"', '""', $name);

        if (@\pg_query($pgsqlAdmin, \sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', $escapedName)) === false) {
            throw new RuntimeException(
                \sprintf(
                    '[provision] PgSQL DROP DATABASE "%s" failed: %s',
                    $name,
                    \pg_last_error($pgsqlAdmin),
                ),
            );
        }

        if (@\pg_query($pgsqlAdmin, \sprintf('CREATE DATABASE "%s"', $escapedName)) === false) {
            throw new RuntimeException(
                \sprintf(
                    '[provision] PgSQL CREATE DATABASE "%s" failed: %s',
                    $name,
                    \pg_last_error($pgsqlAdmin),
                ),
            );
        }

        \printf("[provision] PgSQL: created %s\n", $name);
    }

    \pg_close($pgsqlAdmin);
}
