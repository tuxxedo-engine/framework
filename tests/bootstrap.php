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

require_once __DIR__ . '/../vendor/autoload.php';

$testToken = \getenv('TEST_TOKEN');

if ($testToken !== false && $testToken !== '') {
    \putenv('TUXXEDO_WORKER_ID=' . $testToken);

    $_ENV['TUXXEDO_WORKER_ID'] = $testToken;
    $_SERVER['TUXXEDO_WORKER_ID'] = $testToken;
}

if (DatabaseServerProbe::isMysqlAvailable()) {
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
            \fwrite(
                \STDERR,
                \sprintf(
                    "[bootstrap] MySQL admin connect failed: %s\n",
                    $mysqlAdmin->connect_error ?? 'unknown error',
                ),
            );
        } else {
            $escapedName = \str_replace('`', '``', $mysqlDatabaseName);

            if ($mysqlAdmin->query(\sprintf('DROP DATABASE IF EXISTS `%s`', $escapedName)) === false) {
                \fwrite(
                    \STDERR,
                    \sprintf(
                        "[bootstrap] DROP DATABASE `%s` failed: %s\n",
                        $mysqlDatabaseName,
                        $mysqlAdmin->error,
                    ),
                );
            }

            if ($mysqlAdmin->query(\sprintf('CREATE DATABASE `%s`', $escapedName)) === false) {
                \fwrite(
                    \STDERR,
                    \sprintf(
                        "[bootstrap] CREATE DATABASE `%s` failed: %s\n",
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
        \fwrite(
            \STDERR,
            \sprintf(
                "MySQL admin bootstrap failed: %s\n",
                $exception->getMessage(),
            ),
        );
    }
}
