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
use Tuxxedo\Container\Container;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecord;
use Tuxxedo\Logger\Processor\Info\MemoryInfoInterface;
use Tuxxedo\Logger\Processor\MemoryProcessor;
use Tuxxedo\Temporal\Instant;

class MemoryProcessorTest extends TestCase
{
    public function testAddsMemoryInfo(): void
    {
        $record = (new MemoryProcessor())->process(
            new Container(),
            new LogRecord(
                message: 'message',
                level: LogLevel::INFO,
                timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        self::assertSame(['memory'], \array_keys($record->extra));

        $info = $record->extra['memory'];

        self::assertInstanceOf(MemoryInfoInterface::class, $info);
        self::assertGreaterThan(0, $info->usage);
        self::assertGreaterThanOrEqual($info->usage, $info->peak);
    }
}
