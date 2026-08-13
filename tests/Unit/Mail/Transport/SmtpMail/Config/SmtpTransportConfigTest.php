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

namespace Unit\Mail\Transport\SmtpMail\Config;

use PHPUnit\Framework\TestCase;
use Support\Mail\Transport\StubSmtpSocket;
use Tuxxedo\Container\Container;
use Tuxxedo\Mail\Transport\SmtpMail\Config\SmtpTransportConfig;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTransport;

class SmtpTransportConfigTest extends TestCase
{
    public function testCreateTransportResolvesSmtpTransportFromContainer(): void
    {
        $config = new SmtpTransportConfig();

        $expected = new SmtpTransport(
            config: $config,
            socket: new StubSmtpSocket(),
        );

        $container = new Container();
        $container->singleton($expected);

        self::assertSame(
            $expected,
            $config->createTransport($container),
        );
    }
}
