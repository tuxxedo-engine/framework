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

namespace Support\Mail;

class MailpitServerProbe
{
    private const int CONNECT_ATTEMPTS = 3;
    private const int CONNECT_RETRY_DELAY_MS = 200;
    private const float CONNECT_TIMEOUT_SECONDS = 1.0;

    private static bool $probed = false;
    private static ?string $reason = null;

    public static function isAvailable(): bool
    {
        return self::unavailableReason() === null;
    }

    public static function unavailableReason(): ?string
    {
        if (self::$probed) {
            return self::$reason;
        }

        self::$probed = true;

        $host = MailpitTestEnv::host();

        if ($host === '') {
            return self::$reason = 'TUXXEDO_TEST_MAILPIT_HOST is not set';
        }

        $lastError = 'unknown error';
        $port = MailpitTestEnv::smtpPort();

        for ($attempt = 1; $attempt <= self::CONNECT_ATTEMPTS; $attempt++) {
            $errno = 0;
            $errstr = '';

            $socket = @\fsockopen(
                hostname: $host,
                port: $port,
                error_code: $errno,
                error_message: $errstr,
                timeout: self::CONNECT_TIMEOUT_SECONDS,
            );

            if ($socket !== false) {
                \fclose($socket);

                return self::$reason = null;
            }

            $lastError = $errstr !== ''
                ? $errstr
                : 'connection refused';

            if ($attempt < self::CONNECT_ATTEMPTS) {
                \usleep(self::CONNECT_RETRY_DELAY_MS * 1000);
            }
        }

        return self::$reason = \sprintf(
            'Mailpit SMTP probe on %s:%d failed after %d attempts: %s',
            $host,
            $port,
            self::CONNECT_ATTEMPTS,
            $lastError,
        );
    }
}
