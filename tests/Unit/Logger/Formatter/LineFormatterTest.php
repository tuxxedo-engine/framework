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

namespace Unit\Logger\Formatter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Logger\Formatter\LineFormatter;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecord;
use Tuxxedo\Temporal\Instant;

class LineFormatterTest extends TestCase
{
    /**
     * @return \Generator<array{0: LogLevel, 1: string}>
     */
    public static function levelDataProvider(): \Generator
    {
        foreach (LogLevel::cases() as $level) {
            yield [
                $level,
                '[2026-01-01T12:00:00.000000+00:00] [' . $level->name . '] message' . \PHP_EOL,
            ];
        }
    }

    #[DataProvider('levelDataProvider')]
    public function testFormatsLevel(
        LogLevel $level,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new LineFormatter())->format(
                new LogRecord(
                    message: 'message',
                    level: $level,
                    timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
                ),
            ),
        );
    }

    public function testFormatsInterpolatedMessage(): void
    {
        self::assertSame(
            '[2026-01-01T12:00:00.000000+00:00] [INFO] Hello Kalle' . \PHP_EOL,
            (new LineFormatter())->format(
                new LogRecord(
                    message: 'Hello {name}',
                    level: LogLevel::INFO,
                    timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
                    context: [
                        'name' => 'Kalle',
                    ],
                ),
            ),
        );
    }

    public function testLeavesOutChannelContextExtraAndException(): void
    {
        self::assertSame(
            '[2026-01-01T12:00:00.000000+00:00] [ERROR] Hello' . \PHP_EOL,
            (new LineFormatter())->format(
                new LogRecord(
                    message: 'Hello',
                    level: LogLevel::ERROR,
                    timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
                    context: [
                        'unused' => 'value',
                    ],
                    channel: 'payments',
                    exception: new \RuntimeException('failure'),
                    extra: [
                        'host.name' => 'localhost',
                    ],
                ),
            ),
        );
    }
}
