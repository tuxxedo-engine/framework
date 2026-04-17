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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\SyslogLogger;

class SyslogLoggerTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $logger = new SyslogLogger();

        self::assertSame(0, $logger->total);
    }

    public function testConstructWithCustomOptions(): void
    {
        $logger = new SyslogLogger(
            ident: 'test_app',
            persistent: true,
            facility: \LOG_USER,
            options: \LOG_PID,
        );

        self::assertSame(0, $logger->total);
    }

    public function testLogReturnsSelf(): void
    {
        $logger = new SyslogLogger();

        self::assertSame($logger, $logger->log('test'));
    }

    /**
     * @return \Generator<array{0: LogLevel, 1: \Closure(LoggerInterface $logger): int}>
     */
    public static function logLevelCounterDataProvider(): \Generator
    {
        yield [
            LogLevel::ALERT,
            static function (LoggerInterface $logger): int {
                return $logger->totalAlert;
            },
        ];

        yield [
            LogLevel::CRITICAL,
            static function (LoggerInterface $logger): int {
                return $logger->totalCritical;
            },
        ];

        yield [
            LogLevel::DEBUG,
            static function (LoggerInterface $logger): int {
                return $logger->totalDebug;
            },
        ];

        yield [
            LogLevel::EMERGENCY,
            static function (LoggerInterface $logger): int {
                return $logger->totalEmergency;
            },
        ];

        yield [
            LogLevel::ERROR,
            static function (LoggerInterface $logger): int {
                return $logger->totalError;
            },
        ];

        yield [
            LogLevel::INFO,
            static function (LoggerInterface $logger): int {
                return $logger->totalInfo;
            },
        ];

        yield [
            LogLevel::NOTICE,
            static function (LoggerInterface $logger): int {
                return $logger->totalNotice;
            },
        ];

        yield [
            LogLevel::WARNING,
            static function (LoggerInterface $logger): int {
                return $logger->totalWarning;
            },
        ];
    }

    /**
     * @param \Closure(LoggerInterface $logger): int $propertyReader
     */
    #[DataProvider('logLevelCounterDataProvider')]
    public function testLogIncrementsLevelCounter(
        LogLevel $level,
        \Closure $propertyReader,
    ): void {
        $logger = new SyslogLogger();

        $logger->log(
            message: 'message',
            level: $level,
        );

        self::assertSame(1, $propertyReader($logger));
        self::assertSame(1, $logger->total);
    }

    public function testNonPersistentClosesAfterEachLog(): void
    {
        $logger = new SyslogLogger(
            persistent: false,
        );

        $logger->log('first');
        $logger->log('second');

        self::assertSame(2, $logger->total);
    }

    public function testPersistentStaysOpen(): void
    {
        $logger = new SyslogLogger(
            persistent: true,
        );

        $logger->log('first');
        $logger->log('second');
        $logger->log('third');

        self::assertSame(3, $logger->total);
    }

    public function testLogWithPlaceholders(): void
    {
        $logger = new SyslogLogger();

        $logger->log(
            message: 'User {name} logged in',
            placeholders: [
                'name' => 'Kalle',
            ],
        );

        self::assertSame(1, $logger->total);
    }

    public function testTotalReflectsAllLevels(): void
    {
        $logger = new SyslogLogger();

        foreach (LogLevel::cases() as $level) {
            $logger->log('message', [], $level);
        }

        self::assertSame(\sizeof(LogLevel::cases()), $logger->total);
    }
}
