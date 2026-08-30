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

class MailpitTestEnv
{
    private const int DEFAULT_SMTP_PORT = 1025;
    private const string DEFAULT_API_PATH = '/api/v1';
    private const int DEFAULT_API_PORT = 8025;

    public static function host(): string
    {
        $host = \getenv('TUXXEDO_TEST_MAILPIT_HOST');

        return ($host === false || $host === '')
            ? ''
            : $host;
    }

    public static function smtpPort(): int
    {
        $port = \getenv('TUXXEDO_TEST_MAILPIT_SMTP_PORT');

        if ($port === false || $port === '') {
            return self::DEFAULT_SMTP_PORT;
        }

        return (int) $port;
    }

    public static function apiUrl(): string
    {
        $url = \getenv('TUXXEDO_TEST_MAILPIT_API_URL');

        if ($url !== false && $url !== '') {
            return \rtrim($url, '/');
        }

        return \sprintf(
            'http://%s:%d%s',
            self::host(),
            self::DEFAULT_API_PORT,
            self::DEFAULT_API_PATH,
        );
    }
}
