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

class MysqlTestEnv
{
    public static function host(): string
    {
        return self::stringOrDefault(
            name: 'TUXXEDO_TEST_MYSQL_HOST',
            default: 'localhost',
        );
    }

    public static function port(): ?int
    {
        return self::nullableInt(
            name: 'TUXXEDO_TEST_MYSQL_PORT',
        );
    }

    public static function socket(): ?string
    {
        return self::nullableString(
            name: 'TUXXEDO_TEST_MYSQL_SOCKET',
        );
    }

    public static function username(): string
    {
        return self::stringOrDefault(
            name: 'TUXXEDO_TEST_MYSQL_USER',
            default: '',
        );
    }

    public static function password(): string
    {
        $value = \getenv('TUXXEDO_TEST_MYSQL_PASS');

        return $value === false
            ? ''
            : $value;
    }

    public static function charset(): string
    {
        return self::stringOrDefault(
            name: 'TUXXEDO_TEST_MYSQL_CHARSET',
            default: 'utf8mb4',
        );
    }

    public static function timeout(): ?int
    {
        return self::nullableInt(
            name: 'TUXXEDO_TEST_MYSQL_TIMEOUT',
        );
    }

    public static function adminDatabase(): string
    {
        return self::stringOrDefault(
            name: 'TUXXEDO_TEST_MYSQL_ADMIN_DATABASE',
            default: 'mysql',
        );
    }

    public static function databaseName(): string
    {
        return \sprintf(
            '%s_%s',
            self::stringOrDefault(
                name: 'TUXXEDO_TEST_MYSQL_DATABASE_PREFIX',
                default: 'tuxxedo_test',
            ),
            self::workerId(),
        );
    }

    public static function workerId(): string
    {
        return self::stringOrDefault(
            name: 'TUXXEDO_WORKER_ID',
            default: '0',
        );
    }

    private static function stringOrDefault(
        string $name,
        string $default,
    ): string {
        $value = \getenv($name);

        return $value === false || $value === ''
            ? $default
            : $value;
    }

    private static function nullableString(
        string $name,
    ): ?string {
        $value = \getenv($name);

        return $value === false || $value === ''
            ? null
            : $value;
    }

    private static function nullableInt(
        string $name,
    ): ?int {
        $value = \getenv($name);

        return $value === false || $value === ''
            ? null
            : (int) $value;
    }
}
