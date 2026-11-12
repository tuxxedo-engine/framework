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
use Tuxxedo\Logger\Processor\Info\PhpInfoInterface;
use Tuxxedo\Logger\Processor\PhpProcessor;
use Tuxxedo\Temporal\Instant;

class PhpProcessorTest extends TestCase
{
    public function testAddsPhpInfo(): void
    {
        $record = (new PhpProcessor())->process(
            new Container(),
            new LogRecord(
                message: 'message',
                level: LogLevel::INFO,
                timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        self::assertSame(['php'], \array_keys($record->extra));

        $info = $record->extra['php'];

        self::assertInstanceOf(PhpInfoInterface::class, $info);
        self::assertSame(\PHP_VERSION, $info->version);
        self::assertSame(\PHP_SAPI, $info->sapi);
    }
}
