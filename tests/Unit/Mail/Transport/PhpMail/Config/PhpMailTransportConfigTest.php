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

namespace Unit\Mail\Transport\PhpMail\Config;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Mail\Transport\PhpMail\Config\PhpMailTransportConfig;
use Tuxxedo\Mail\Transport\PhpMail\PhpMailTransport;

class PhpMailTransportConfigTest extends TestCase
{
    public function testCreateTransportResolvesPhpMailTransportFromContainer(): void
    {
        $expected = new PhpMailTransport();

        $container = new Container();
        $container->singleton($expected);

        $config = new PhpMailTransportConfig();

        self::assertSame(
            $expected,
            $config->createTransport($container),
        );
    }
}
