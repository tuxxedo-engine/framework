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
use Support\Logger\Processor\ThrowingProcessor;
use Tuxxedo\Container\Container;
use Tuxxedo\Logger\Config\GroupLoggerConfig;
use Tuxxedo\Logger\Config\StreamLoggerConfig;
use Tuxxedo\Logger\GroupLogger;
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\LoggerException;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class GroupLoggerConfigTest extends TestCase
{
    public function testCreatesGroupOfInnerLoggers(): void
    {
        $first = \tempnam(\sys_get_temp_dir(), 'tuxxedo_test_');
        $second = \tempnam(\sys_get_temp_dir(), 'tuxxedo_test_');

        self::assertIsString($first);
        self::assertIsString($second);

        $container = new Container();
        $container->singleton(
            new FixedClock(
                instant: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        try {
            $logger = (new GroupLoggerConfig(
                loggers: [
                    new StreamLoggerConfig(
                        file: $first,
                    ),
                    new StreamLoggerConfig(
                        file: $second,
                    ),
                ],
            ))->createLogger($container);

            self::assertInstanceOf(GroupLogger::class, $logger);

            $logger->info('message');

            $expected = '[2026-01-01T12:00:00.000000+00:00] [INFO] message' . \PHP_EOL;

            self::assertSame($expected, \file_get_contents($first));
            self::assertSame($expected, \file_get_contents($second));
        } finally {
            \unlink($first);
            \unlink($second);
        }
    }

    public function testEmptyGroup(): void
    {
        $logger = (new GroupLoggerConfig(
            loggers: [],
        ))->createLogger(new Container());

        self::assertInstanceOf(GroupLogger::class, $logger);
    }

    public function testPassesProcessorsAndFailureMode(): void
    {
        $logger = (new GroupLoggerConfig(
            loggers: [],
            processors: [
                ThrowingProcessor::class,
            ],
            processorFailure: LogFailureMode::STRICT,
        ))->createLogger(new Container());

        self::assertInstanceOf(GroupLogger::class, $logger);
        self::assertSame(LogFailureMode::STRICT, $logger->processorFailure);

        self::expectException(LoggerException::class);

        $logger->info('message');
    }
}
