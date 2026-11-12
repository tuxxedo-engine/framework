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

namespace Unit\Logger\Config;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Logger\Config\LoggerConfigInterface;
use Tuxxedo\Logger\Config\NullLoggerConfig;
use Tuxxedo\Logger\NullLogger;

class NullLoggerConfigTest extends TestCase
{
    public function testCreatesNullLogger(): void
    {
        self::assertInstanceOf(
            NullLogger::class,
            (new NullLoggerConfig())->createLogger(new Container()),
        );
    }

    public function testIsDefaultLoggerConfig(): void
    {
        self::assertInstanceOf(
            NullLoggerConfig::class,
            (new Container())->resolve(LoggerConfigInterface::class),
        );
    }
}
