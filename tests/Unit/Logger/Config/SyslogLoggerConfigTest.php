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
use Tuxxedo\Logger\Config\SyslogLoggerConfig;
use Tuxxedo\Logger\Formatter\LogFormat;
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\LoggerException;
use Tuxxedo\Logger\SyslogLogger;

class SyslogLoggerConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new SyslogLoggerConfig();

        self::assertSame('tuxxedo', $config->ident);
        self::assertFalse($config->persistent);
        self::assertSame(\LOG_USER, $config->facility);
        self::assertSame(\LOG_PID | \LOG_ODELAY, $config->options);
        self::assertNull($config->format);
        self::assertSame(LogFailureMode::MARKER, $config->unencodable);
    }

    public function testCreatesSyslogLoggerWithoutFormat(): void
    {
        $logger = (new SyslogLoggerConfig())->createLogger(new Container());

        self::assertInstanceOf(SyslogLogger::class, $logger);

        $logger->info('message');

        self::assertSame(1, $logger->total);
    }

    public function testCreatesSyslogLoggerWithFormat(): void
    {
        $logger = (new SyslogLoggerConfig(
            ident: 'tuxxedo_test',
            persistent: true,
            options: \LOG_PID,
            format: LogFormat::JSON,
        ))->createLogger(new Container());

        self::assertInstanceOf(SyslogLogger::class, $logger);

        $logger->info('message');

        self::assertSame(1, $logger->total);
    }

    public function testPassesProcessorsAndFailureMode(): void
    {
        $logger = (new SyslogLoggerConfig(
            processors: [
                ThrowingProcessor::class,
            ],
            processorFailure: LogFailureMode::STRICT,
        ))->createLogger(new Container());

        self::assertInstanceOf(SyslogLogger::class, $logger);
        self::assertSame(LogFailureMode::STRICT, $logger->processorFailure);

        self::expectException(LoggerException::class);

        $logger->info('message');
    }
}
