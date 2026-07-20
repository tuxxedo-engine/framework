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
    private const int CONNECT_ATTEMPTS = 3;
    private const int CONNECT_RETRY_DELAY_MS = 200;

    private static bool $mysqlProbed = false;
    private static ?string $mysqlReason = null;

    private static bool $pgsqlProbed = false;
    private static ?string $pgsqlReason = null;

    public static function isMysqlAvailable(): bool
    {
        return self::mysqlUnavailableReason() === null;
    }

    public static function isPgsqlAvailable(): bool
    {
        return self::pgsqlUnavailableReason() === null;
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

        $lastError = 'unknown error';

        for ($attempt = 1; $attempt <= self::CONNECT_ATTEMPTS; $attempt++) {
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

                if ($connection->connect_errno === 0) {
                    $connection->close();

                    return self::$mysqlReason = null;
                }

                $lastError = $connection->connect_error ?? 'unknown error';
            } catch (\mysqli_sql_exception $exception) {
                $lastError = $exception->getMessage();
            }

            if ($attempt < self::CONNECT_ATTEMPTS) {
                \usleep(self::CONNECT_RETRY_DELAY_MS * 1000);
            }
        }

        return self::$mysqlReason = \sprintf(
            'MySQL connect probe failed after %d attempts: %s',
            self::CONNECT_ATTEMPTS,
            $lastError,
        );
    }

    public static function pgsqlUnavailableReason(): ?string
    {
        if (self::$pgsqlProbed) {
            return self::$pgsqlReason;
        }

        self::$pgsqlProbed = true;

        $host = \getenv('TUXXEDO_TEST_PGSQL_HOST');

        if ($host === false || $host === '') {
            return self::$pgsqlReason = 'TUXXEDO_TEST_PGSQL_HOST is not set';
        }

        if (!\extension_loaded('pgsql')) {
            return self::$pgsqlReason = 'pgsql extension is not loaded';
        }

        $port = \getenv('TUXXEDO_TEST_PGSQL_PORT');
        $user = \getenv('TUXXEDO_TEST_PGSQL_USER');
        $pass = \getenv('TUXXEDO_TEST_PGSQL_PASS');
        $adminDb = \getenv('TUXXEDO_TEST_PGSQL_ADMIN_DATABASE');

        $dsn = [
            'host=' . self::pgsqlQuote($host),
            'sslmode=' . self::pgsqlQuote('disable'),
        ];

        if ($port !== false && $port !== '') {
            $dsn[] = 'port=' . self::pgsqlQuote($port);
        }

        if ($user !== false && $user !== '') {
            $dsn[] = 'user=' . self::pgsqlQuote($user);
        }

        if ($pass !== false && $pass !== '') {
            $dsn[] = 'password=' . self::pgsqlQuote($pass);
        }

        $dsn[] = 'dbname=' . self::pgsqlQuote(
            $adminDb === false || $adminDb === ''
                ? 'postgres'
                : $adminDb,
        );

        $dsn[] = 'connect_timeout=' . self::pgsqlQuote('3');

        $dsnString = \join(' ', $dsn);
        $lastError = 'unknown error';

        for ($attempt = 1; $attempt <= self::CONNECT_ATTEMPTS; $attempt++) {
            $connection = @\pg_connect($dsnString);

            if ($connection !== false) {
                \pg_close($connection);

                return self::$pgsqlReason = null;
            }

            $errorInfo = \error_get_last();
            $lastError = $errorInfo['message'] ?? 'unknown error';

            if ($attempt < self::CONNECT_ATTEMPTS) {
                \usleep(self::CONNECT_RETRY_DELAY_MS * 1000);
            }
        }

        return self::$pgsqlReason = \sprintf(
            'PgSQL connect probe failed after %d attempts: %s',
            self::CONNECT_ATTEMPTS,
            $lastError,
        );
    }

    private static function pgsqlQuote(
        string $value,
    ): string {
        return "'" . \addcslashes($value, "\\'") . "'";
    }
}
