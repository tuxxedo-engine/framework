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

namespace Unit\Mail\Transport\FileMail\Config;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Mail\Transport\FileMail\Config\FileMailTransportConfig;
use Tuxxedo\Mail\Transport\FileMail\FileMailTransport;

class FileMailTransportConfigTest extends TestCase
{
    public function testCreateTransportResolvesFileMailTransportFromContainer(): void
    {
        $config = new FileMailTransportConfig(
            directory: '/tmp',
        );

        $expected = new FileMailTransport(
            config: $config,
        );

        $container = new Container();
        $container->singleton($expected);

        self::assertSame(
            $expected,
            $config->createTransport($container),
        );
    }
}
