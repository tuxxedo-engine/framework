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
use Tuxxedo\Logger\Processor\EngineProcessor;
use Tuxxedo\Logger\Processor\Info\EngineInfoInterface;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Version;

class EngineProcessorTest extends TestCase
{
    public function testAddsEngineInfo(): void
    {
        $record = (new EngineProcessor())->process(
            new Container(),
            new LogRecord(
                message: 'message',
                level: LogLevel::INFO,
                timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        self::assertSame(['engine'], \array_keys($record->extra));

        $info = $record->extra['engine'];

        self::assertInstanceOf(EngineInfoInterface::class, $info);
        self::assertSame(Version::SIMPLE, $info->version);
        self::assertSame(Version::CODENAME, $info->codename);
        self::assertSame(Version::PREVIEW, $info->preview);
    }
}
