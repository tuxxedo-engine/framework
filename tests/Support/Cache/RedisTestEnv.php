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

namespace Support\Cache;

class RedisTestEnv
{
    private const int DEFAULT_PORT = 6379;

    public static function host(): string
    {
        $host = \getenv('TUXXEDO_TEST_REDIS_HOST');

        return ($host === false || $host === '')
            ? ''
            : $host;
    }

    public static function port(): int
    {
        $port = \getenv('TUXXEDO_TEST_REDIS_PORT');

        if ($port === false || $port === '') {
            return self::DEFAULT_PORT;
        }

        return (int) $port;
    }
}
