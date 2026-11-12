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
use Tuxxedo\Logger\Config\FilterLoggerConfig;
use Tuxxedo\Logger\Config\StreamLoggerConfig;
use Tuxxedo\Logger\FilterLogger;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class FilterLoggerConfigTest extends TestCase
{
    public function testCreatesFilterAroundInnerLogger(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'tuxxedo_test_');

        self::assertIsString($file);

        $container = new Container();
        $container->singleton(
            new FixedClock(
                instant: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        try {
            $logger = (new FilterLoggerConfig(
                minimum: LogLevel::WARNING,
                logger: new StreamLoggerConfig(
                    file: $file,
                ),
            ))->createLogger($container);

            self::assertInstanceOf(FilterLogger::class, $logger);
            self::assertSame(LogLevel::WARNING, $logger->minimum);

            $logger->info('info');
            $logger->error('error');

            self::assertSame(
                '[2026-01-01T12:00:00.000000+00:00] [ERROR] error' . \PHP_EOL,
                \file_get_contents($file),
            );
        } finally {
            \unlink($file);
        }
    }
}
