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
use Tuxxedo\Logger\LogMessageFormatter;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\NullLogger;

class LoggerTest extends TestCase
{
    /**
     * @return \Generator<array{0: string, 1: array<string, scalar>, 2: string}>
     */
    public static function interpolateDataProvider(): \Generator
    {
        yield [
            'Hello World',
            [],
            'Hello World',
        ];

        yield [
            'Hello {name}',
            [
                'name' => 'Kalle',
            ],
            'Hello Kalle',
        ];

        yield [
            '{greeting} {name}, you are {age}',
            [
                'greeting' => 'Hello',
                'name' => 'Kalle',
                'age' => 36,
            ],
            'Hello Kalle, you are 36',
        ];

        yield [
            'No placeholders here',
            [
                'unused' => 'value',
            ],
            'No placeholders here',
        ];

        yield [
            'Value is {val}',
            [
                'val' => 3.14,
            ],
            'Value is 3.14',
        ];

        yield [
            'Flag is {flag}',
            [
                'flag' => true,
            ],
            'Flag is 1',
        ];

        yield [
            'Count is {count}',
            [
                'count' => 0,
            ],
            'Count is 0',
        ];
    }

    /**
     * @param array<string, scalar> $placeholders
     */
    #[DataProvider('interpolateDataProvider')]
    public function testInterpolate(
        string $message,
        array $placeholders,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new LogMessageFormatter())->interpolate($message, $placeholders),
        );
    }

    /**
     * @return \Generator<array{0: LogLevel, 1: string}>
     */
    public static function formatLogLevelDataProvider(): \Generator
    {
        yield [
            LogLevel::ALERT,
            '[ALERT] message',
        ];

        yield [
            LogLevel::CRITICAL,
            '[CRITICAL] message',
        ];

        yield [
            LogLevel::DEBUG,
            '[DEBUG] message',
        ];

        yield [
            LogLevel::EMERGENCY,
            '[EMERGENCY] message',
        ];

        yield [
            LogLevel::ERROR,
            '[ERROR] message',
        ];

        yield [
            LogLevel::INFO,
            '[INFO] message',
        ];

        yield [
            LogLevel::NOTICE,
            '[NOTICE] message',
        ];

        yield [
            LogLevel::WARNING,
            '[WARNING] message',
        ];
    }

    #[DataProvider('formatLogLevelDataProvider')]
    public function testFormatLogLevel(
        LogLevel $level,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new LogMessageFormatter())->formatLogLevel(
                message: 'message',
                level: $level,
            ),
        );
    }

    public function testFormatTimestamp(): void
    {
        $timestamp = new \DateTimeImmutable('2026-01-01T12:00:00.000000+00:00');

        self::assertSame(
            '[2026-01-01T12:00:00.000000+00:00] message',
            (new LogMessageFormatter())->formatTimestamp(
                message: 'message',
                timestamp: $timestamp,
            ),
        );
    }

    public function testFormatWithNoLevelNoTimestamp(): void
    {
        $timestamp = new \DateTimeImmutable('2026-01-01T12:00:00.000000+00:00');

        self::assertSame(
            '[2026-01-01T12:00:00.000000+00:00] Hello' . \PHP_EOL,
            (new LogMessageFormatter())->format(
                message: 'Hello',
                timestamp: $timestamp,
            ),
        );
    }

    public function testFormatWithLevel(): void
    {
        $timestamp = new \DateTimeImmutable('2026-01-01T12:00:00.000000+00:00');

        self::assertSame(
            '[2026-01-01T12:00:00.000000+00:00] [ERROR] Hello' . \PHP_EOL,
            (new LogMessageFormatter())->format(
                message: 'Hello',
                level: LogLevel::ERROR,
                timestamp: $timestamp,
            ),
        );
    }

    public function testFormatWithPlaceholders(): void
    {
        $timestamp = new \DateTimeImmutable('2026-01-01T12:00:00.000000+00:00');

        self::assertSame(
            '[2026-01-01T12:00:00.000000+00:00] [INFO] Hello Kalle' . \PHP_EOL,
            (new LogMessageFormatter())->format(
                message: 'Hello {name}',
                placeholders: [
                    'name' => 'Kalle',
                ],
                level: LogLevel::INFO,
                timestamp: $timestamp,
            ),
        );
    }

    public function testFormatWithNullTimestampUsesCurrentTime(): void
    {
        $result = (new LogMessageFormatter())->format('message');

        self::assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+[+-]\d{2}:\d{2}] message/',
            $result,
        );

        self::assertStringEndsWith(\PHP_EOL, $result);
    }

    public function testFormatEndsWithNewline(): void
    {
        $result = (new LogMessageFormatter())->format('test');

        self::assertStringEndsWith(\PHP_EOL, $result);
    }

    public function testNullLoggerReturnsSelf(): void
    {
        $logger = new NullLogger();

        self::assertSame($logger, $logger->log('test'));
    }

    public function testNullLoggerIncrementsTotalCounter(): void
    {
        $logger = new NullLogger();

        self::assertSame(0, $logger->total);

        $logger->log('one');

        self::assertSame(1, $logger->total);

        $logger->log('two');
        $logger->log('three');

        self::assertSame(3, $logger->total);
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
    public function testNullLoggerIncrementsLevelCounter(
        LogLevel $level,
        \Closure $propertyReader,
    ): void {
        $logger = new NullLogger();

        self::assertSame(0, $propertyReader($logger));

        $logger->log(
            message: 'message',
            level: $level,
        );

        self::assertSame(1, $propertyReader($logger));
        self::assertSame(1, $logger->total);
    }

    /**
     * @param \Closure(LoggerInterface $logger): int $propertyReader
     */
    #[DataProvider('logLevelCounterDataProvider')]
    public function testNullLoggerGetTotalByLogLevel(
        LogLevel $level,
        \Closure $propertyReader,
    ): void {
        $logger = new NullLogger();

        $logger->log(
            message: 'message',
            level: $level,
        );

        self::assertSame(1, $logger->getTotalByLogLevel($level));
    }


    /**
     * @return \Generator<array{0: \Closure(LoggerInterface $logger, string $message): LoggerInterface, 1: LogLevel}>
     */
    public static function convenienceMethodDataProvider(): \Generator
    {
        yield [
            static function (LoggerInterface $logger, string $message): LoggerInterface {
                return $logger->alert($message);
            },
            LogLevel::ALERT,
        ];

        yield [
            static function (LoggerInterface $logger, string $message): LoggerInterface {
                return $logger->critical($message);
            },
            LogLevel::CRITICAL,
        ];

        yield [
            static function (LoggerInterface $logger, string $message): LoggerInterface {
                return $logger->debug($message);
            },
            LogLevel::DEBUG,
        ];

        yield [
            static function (LoggerInterface $logger, string $message): LoggerInterface {
                return $logger->emergency($message);
            },
            LogLevel::EMERGENCY,
        ];

        yield [
            static function (LoggerInterface $logger, string $message): LoggerInterface {
                return $logger->error($message);
            },
            LogLevel::ERROR,
        ];

        yield [
            static function (LoggerInterface $logger, string $message): LoggerInterface {
                return $logger->info($message);
            },
            LogLevel::INFO,
        ];

        yield [
            static function (LoggerInterface $logger, string $message): LoggerInterface {
                return $logger->notice($message);
            },
            LogLevel::NOTICE,
        ];

        yield [
            static function (LoggerInterface $logger, string $message): LoggerInterface {
                return $logger->warning($message);
            },
            LogLevel::WARNING,
        ];
    }

    /**
     * @param \Closure(LoggerInterface $logger, string $message): LoggerInterface $setter
     * @param LogLevel $level
     * @return void
     */
    #[DataProvider('convenienceMethodDataProvider')]
    public function testNullLoggerConvenienceMethodDelegatesToLog(
        \Closure $setter,
        LogLevel $level,
    ): void {
        $logger = new NullLogger();

        self::assertSame($logger, $setter($logger, 'message'));
        self::assertSame(1, $logger->getTotalByLogLevel($level));
        self::assertSame(1, $logger->total);
    }

    public function testNullLoggerTotalReflectsAllLevels(): void
    {
        $logger = new NullLogger();

        foreach (LogLevel::cases() as $level) {
            $logger->log(
                message: 'message',
                level: $level,
            );
        }

        self::assertSame(\sizeof(LogLevel::cases()), $logger->total);
    }

    public function testNullLoggerAcceptsCustomFormatter(): void
    {
        $logger = new NullLogger(
            formatter: new LogMessageFormatter(),
        );

        $logger->log('test');

        self::assertSame(1, $logger->total);
    }
}
