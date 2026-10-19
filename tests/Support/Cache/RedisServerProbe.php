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

class RedisServerProbe
{
    private const float CONNECT_TIMEOUT_SECONDS = 1.0;

    private static bool $probed = false;
    private static ?string $reason = null;

    public static function unavailableReason(): ?string
    {
        if (self::$probed) {
            return self::$reason;
        }

        self::$probed = true;

        if (!\extension_loaded('redis')) {
            return self::$reason = 'ext-redis is not loaded';
        }

        $host = RedisTestEnv::host();

        if ($host === '') {
            return self::$reason = 'TUXXEDO_TEST_REDIS_HOST is not set';
        }

        $errno = 0;
        $errstr = '';

        $socket = @\fsockopen(
            hostname: $host,
            port: RedisTestEnv::port(),
            error_code: $errno,
            error_message: $errstr,
            timeout: self::CONNECT_TIMEOUT_SECONDS,
        );

        if ($socket === false) {
            return self::$reason = \sprintf(
                'Redis probe on %s:%d failed: %s',
                $host,
                RedisTestEnv::port(),
                $errstr !== '' ? $errstr : 'connection refused',
            );
        }

        \fclose($socket);

        return self::$reason = null;
    }
}
