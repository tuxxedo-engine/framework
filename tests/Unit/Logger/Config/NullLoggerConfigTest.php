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
use Tuxxedo\Logger\Config\LoggerConfigInterface;
use Tuxxedo\Logger\Config\NullLoggerConfig;
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\LoggerException;
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

    public function testDefaultsToNoProcessorsAndMarker(): void
    {
        $config = new NullLoggerConfig();

        self::assertSame([], $config->processors);
        self::assertSame(LogFailureMode::MARKER, $config->processorFailure);
    }

    public function testPassesProcessorsAndFailureMode(): void
    {
        $logger = (new NullLoggerConfig(
            processors: [
                ThrowingProcessor::class,
            ],
            processorFailure: LogFailureMode::STRICT,
        ))->createLogger(new Container());

        self::assertInstanceOf(NullLogger::class, $logger);
        self::assertSame(LogFailureMode::STRICT, $logger->processorFailure);

        self::expectException(LoggerException::class);

        $logger->info('message');
    }
}
