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

class DatabaseServerProbe
{
    private static bool $mysqlProbed = false;
    private static ?string $mysqlReason = null;

    public static function isMysqlAvailable(): bool
    {
        return self::mysqlUnavailableReason() === null;
    }

    public static function mysqlUnavailableReason(): ?string
    {
        if (self::$mysqlProbed) {
            return self::$mysqlReason;
        }

        self::$mysqlProbed = true;

        $host = \getenv('TUXXEDO_TEST_MYSQL_HOST');

        if ($host === false || $host === '') {
            return self::$mysqlReason = 'TUXXEDO_TEST_MYSQL_HOST is not set';
        }

        if (!\extension_loaded('mysqli')) {
            return self::$mysqlReason = 'mysqli extension is not loaded';
        }

        $port = \getenv('TUXXEDO_TEST_MYSQL_PORT');
        $portValue = $port === false || $port === ''
            ? null
            : (int) $port;

        $user = \getenv('TUXXEDO_TEST_MYSQL_USER');
        $pass = \getenv('TUXXEDO_TEST_MYSQL_PASS');
        $adminDb = \getenv('TUXXEDO_TEST_MYSQL_ADMIN_DATABASE');

        try {
            $connection = new \mysqli(
                hostname: $host,
                username: ($user === false || $user === '')
                    ? null
                    : $user,
                password: ($pass === false)
                    ? null
                    : $pass,
                database: ($adminDb === false || $adminDb === '')
                    ? null
                    : $adminDb,
                port: $portValue,
            );

            if ($connection->connect_errno !== 0) {
                return self::$mysqlReason = \sprintf(
                    'MySQL connect probe failed: %s',
                    $connection->connect_error ?? 'unknown error',
                );
            }

            $connection->close();

            return self::$mysqlReason = null;
        } catch (\mysqli_sql_exception $exception) {
            return self::$mysqlReason = \sprintf(
                'MySQL connect probe failed: %s',
                $exception->getMessage(),
            );
        }
    }
}
