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

namespace Unit\Logger\Processor;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Application\Config\AppConfig;
use Tuxxedo\Application\Environment;
use Tuxxedo\Container\Container;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecord;
use Tuxxedo\Logger\Processor\AppProcessor;
use Tuxxedo\Logger\Processor\Info\AppInfoInterface;
use Tuxxedo\Temporal\Instant;

class AppProcessorTest extends TestCase
{
    public function testAddsAppInfo(): void
    {
        $container = new Container();
        $container->singleton(
            new AppConfig(
                name: 'Engine',
                version: '0.1.0',
                environment: Environment::TESTING,
                url: 'https://tuxxedo.dev/',
            ),
        );

        $record = (new AppProcessor())->process(
            $container,
            new LogRecord(
                message: 'message',
                level: LogLevel::INFO,
                timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        self::assertSame(['app'], \array_keys($record->extra));

        $info = $record->extra['app'];

        self::assertInstanceOf(AppInfoInterface::class, $info);
        self::assertSame('Engine', $info->name);
        self::assertSame('0.1.0', $info->version);
        self::assertSame(Environment::TESTING, $info->environment);
    }
}
