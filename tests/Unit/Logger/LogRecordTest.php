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

namespace Unit\Logger;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecord;
use Tuxxedo\Temporal\Instant;

class LogRecordTest extends TestCase
{
    public function testDefaults(): void
    {
        $timestamp = Instant::parse(input: '2026-01-01T12:00:00.000000+00:00');
        $record = new LogRecord(
            message: 'message',
            level: LogLevel::INFO,
            timestamp: $timestamp,
        );

        self::assertSame('message', $record->message);
        self::assertSame(LogLevel::INFO, $record->level);
        self::assertSame($timestamp, $record->timestamp);
        self::assertSame([], $record->context);
        self::assertNull($record->channel);
        self::assertNull($record->exception);
        self::assertSame([], $record->extra);
    }

    public function testWithExtraReturnsNewRecord(): void
    {
        $record = new LogRecord(
            message: 'message',
            level: LogLevel::INFO,
            timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            context: [
                'key' => 'value',
            ],
            extra: [
                'app.name' => 'Engine',
            ],
        );

        $extended = $record->withExtra('host.name', 'localhost');

        self::assertNotSame($record, $extended);
        self::assertSame(['app.name' => 'Engine'], $record->extra);
        self::assertSame(
            [
                'app.name' => 'Engine',
                'host.name' => 'localhost',
            ],
            $extended->extra,
        );
        self::assertSame($record->context, $extended->context);
        self::assertSame($record->message, $extended->message);
    }

    public function testWithExtraOverwritesKey(): void
    {
        $record = new LogRecord(
            message: 'message',
            level: LogLevel::INFO,
            timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            extra: [
                'key' => 'old',
            ],
        );

        self::assertSame(['key' => 'new'], $record->withExtra('key', 'new')->extra);
    }
}
