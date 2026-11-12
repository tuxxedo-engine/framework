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
use Tuxxedo\Logger\Config\MemoryLoggerConfig;
use Tuxxedo\Logger\MemoryLogger;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class MemoryLoggerConfigTest extends TestCase
{
    public function testCreatesMemoryLoggerWithContainerClock(): void
    {
        $clock = new FixedClock(
            instant: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
        );

        $container = new Container();
        $container->singleton($clock);

        $logger = (new MemoryLoggerConfig())->createLogger($container);

        self::assertInstanceOf(MemoryLogger::class, $logger);

        $logger->info('message');

        self::assertSame($clock->instant, $logger->records[0]->timestamp);
    }
}
