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

class MailpitAuthServerProbe
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

        $host = MailpitAuthTestEnv::host();

        if ($host === '') {
            return self::$reason = 'TUXXEDO_TEST_MAILPIT_AUTH_HOST is not set';
        }

        $smtpReason = self::probeSmtp(
            host: $host,
            port: MailpitAuthTestEnv::smtpPort(),
        );

        if ($smtpReason !== null) {
            return self::$reason = $smtpReason;
        }

        $apiReason = self::probeApi(
            apiUrl: MailpitAuthTestEnv::apiUrl(),
        );

        if ($apiReason !== null) {
            return self::$reason = $apiReason;
        }

        return self::$reason = null;
    }

    private static function probeSmtp(
        string $host,
        int $port,
    ): ?string {
        $lastError = 'unknown error';

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

                return null;
            }

            $lastError = $errstr !== ''
                ? $errstr
                : 'connection refused';

            if ($attempt < self::CONNECT_ATTEMPTS) {
                \usleep(self::CONNECT_RETRY_DELAY_MS * 1000);
            }
        }

        return \sprintf(
            'Mailpit-auth SMTP probe on %s:%d failed after %d attempts: %s',
            $host,
            $port,
            self::CONNECT_ATTEMPTS,
            $lastError,
        );
    }

    private static function probeApi(
        string $apiUrl,
    ): ?string {
        $context = \stream_context_create(
            options: [
                'http' => [
                    'method' => 'GET',
                    'ignore_errors' => true,
                    'timeout' => self::CONNECT_TIMEOUT_SECONDS,
                    'header' => "Accept: application/json\r\n",
                ],
            ],
        );

        $response = @\file_get_contents(
            filename: $apiUrl . '/messages',
            context: $context,
        );

        if ($response === false) {
            return \sprintf(
                'Mailpit-auth API probe on %s failed (endpoint unreachable)',
                $apiUrl,
            );
        }

        return null;
    }
}
