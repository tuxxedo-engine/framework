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

$testToken = \getenv('TEST_TOKEN');

if ($testToken !== false && $testToken !== '') {
    \putenv('TUXXEDO_WORKER_ID=' . $testToken);

    $_ENV['TUXXEDO_WORKER_ID'] = $testToken;
    $_SERVER['TUXXEDO_WORKER_ID'] = $testToken;
}

$skipBootstrapDb = \getenv('TUXXEDO_TEST_SKIP_BOOTSTRAP_DB') === '1';

if (!$skipBootstrapDb && DatabaseServerProbe::isMysqlAvailable()) {
    $mysqlDatabaseName = MysqlTestEnv::databaseName();

    try {
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
                    '[bootstrap] MySQL admin connect failed: %s',
                    $mysqlAdmin->connect_error ?? 'unknown error',
                ),
            );
        } else {
            $escapedName = \str_replace('`', '``', $mysqlDatabaseName);

            if ($mysqlAdmin->query(\sprintf('DROP DATABASE IF EXISTS `%s`', $escapedName)) === false) {
                throw new RuntimeException(
                    \sprintf(
                        '[bootstrap] DROP DATABASE `%s` failed: %s',
                        $mysqlDatabaseName,
                        $mysqlAdmin->error,
                    ),
                );
            }

            if ($mysqlAdmin->query(\sprintf('CREATE DATABASE `%s`', $escapedName)) === false) {
                throw new RuntimeException(
                    \sprintf(
                        '[bootstrap] CREATE DATABASE `%s` failed: %s',
                        $mysqlDatabaseName,
                        $mysqlAdmin->error,
                    ),
                );
            }

            $mysqlAdmin->close();

            \register_shutdown_function(
                static function () use ($mysqlDatabaseName): void {
                    $keepDatabase = \getenv('TUXXEDO_TEST_KEEP_DATABASE');

                    if ($keepDatabase === '1' || $keepDatabase === 'true') {
                        return;
                    }

                    try {
                        $shutdownAdmin = new mysqli(
                            hostname: MysqlTestEnv::host(),
                            username: MysqlTestEnv::username(),
                            password: MysqlTestEnv::password(),
                            database: MysqlTestEnv::adminDatabase(),
                            port: MysqlTestEnv::port(),
                        );

                        if ($shutdownAdmin->connect_errno !== 0) {
                            return;
                        }

                        $shutdownAdmin->query(
                            \sprintf(
                                'DROP DATABASE IF EXISTS `%s`',
                                \str_replace('`', '``', $mysqlDatabaseName),
                            ),
                        );

                        $shutdownAdmin->close();
                    } catch (mysqli_sql_exception) {
                    }
                },
            );
        }
    } catch (mysqli_sql_exception $exception) {
        throw new RuntimeException(
            \sprintf(
                '[bootstrap] MySQL admin bootstrap failed: %s',
                $exception->getMessage(),
            ),
            previous: $exception,
        );
    }
}

if (!$skipBootstrapDb && DatabaseServerProbe::isPgsqlAvailable()) {
    $pgsqlDatabaseName = PgsqlTestEnv::databaseName();

    $pgConnect = static function (string $dsn): PgSql\Connection|false {
        return @\pg_connect($dsn);
    };

    $pgsqlQuote = static function (string $value): string {
        return "'" . \addcslashes($value, "\\'") . "'";
    };

    $buildPgsqlAdminDsn = static function () use ($pgsqlQuote): string {
        $parts = [
            'host=' . $pgsqlQuote(PgsqlTestEnv::host()),
            'sslmode=' . $pgsqlQuote('disable'),
        ];

        $port = PgsqlTestEnv::port();

        if ($port !== null) {
            $parts[] = 'port=' . $pgsqlQuote((string) $port);
        }

        $user = PgsqlTestEnv::username();

        if ($user !== '') {
            $parts[] = 'user=' . $pgsqlQuote($user);
        }

        $pass = PgsqlTestEnv::password();

        if ($pass !== '') {
            $parts[] = 'password=' . $pgsqlQuote($pass);
        }

        $parts[] = 'dbname=' . $pgsqlQuote(PgsqlTestEnv::adminDatabase());

        return \join(' ', $parts);
    };

    $pgsqlAdmin = $pgConnect($buildPgsqlAdminDsn());

    if (!$pgsqlAdmin instanceof PgSql\Connection) {
        $errorInfo = \error_get_last();

        throw new RuntimeException(
            \sprintf(
                '[bootstrap] PgSQL admin connect failed: %s',
                $errorInfo['message'] ?? 'unknown error',
            ),
        );
    } else {
        $escapedName = \str_replace('"', '""', $pgsqlDatabaseName);

        if (@\pg_query($pgsqlAdmin, \sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', $escapedName)) === false) {
            throw new RuntimeException(
                \sprintf(
                    '[bootstrap] DROP DATABASE "%s" failed: %s',
                    $pgsqlDatabaseName,
                    \pg_last_error($pgsqlAdmin),
                ),
            );
        }

        if (@\pg_query($pgsqlAdmin, \sprintf('CREATE DATABASE "%s"', $escapedName)) === false) {
            throw new RuntimeException(
                \sprintf(
                    '[bootstrap] CREATE DATABASE "%s" failed: %s',
                    $pgsqlDatabaseName,
                    \pg_last_error($pgsqlAdmin),
                ),
            );
        }

        \pg_close($pgsqlAdmin);

        \register_shutdown_function(
            static function () use ($pgsqlDatabaseName, $buildPgsqlAdminDsn, $pgConnect): void {
                $keepDatabase = \getenv('TUXXEDO_TEST_KEEP_DATABASE');

                if ($keepDatabase === '1' || $keepDatabase === 'true') {
                    return;
                }

                $shutdownAdmin = $pgConnect($buildPgsqlAdminDsn());

                if (!$shutdownAdmin instanceof PgSql\Connection) {
                    return;
                }

                @\pg_query(
                    $shutdownAdmin,
                    \sprintf(
                        'DROP DATABASE IF EXISTS "%s" WITH (FORCE)',
                        \str_replace('"', '""', $pgsqlDatabaseName),
                    ),
                );

                \pg_close($shutdownAdmin);
            },
        );
    }
}
