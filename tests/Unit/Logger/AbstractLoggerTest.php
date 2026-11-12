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
use Tuxxedo\Logger\ChildLogger;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\MemoryLogger;
use Tuxxedo\Logger\NullLogger;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class AbstractLoggerTest extends TestCase
{
    private function createClock(): FixedClock
    {
        return new FixedClock(
            instant: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
        );
    }

    public function testLogReturnsSelf(): void
    {
        $logger = new NullLogger();

        self::assertSame($logger, $logger->log('test'));
    }

    public function testLogBuildsRecord(): void
    {
        $clock = $this->createClock();
        $exception = new \RuntimeException('failure');
        $logger = new MemoryLogger(
            clock: $clock,
        );

        $logger->log(
            message: 'Hello {name}',
            context: [
                'name' => 'Kalle',
            ],
            level: LogLevel::WARNING,
            exception: $exception,
        );

        $record = $logger->records[0];

        self::assertSame('Hello {name}', $record->message);
        self::assertSame(['name' => 'Kalle'], $record->context);
        self::assertSame(LogLevel::WARNING, $record->level);
        self::assertSame($clock->instant, $record->timestamp);
        self::assertNull($record->channel);
        self::assertSame($exception, $record->exception);
        self::assertSame([], $record->extra);
    }

    public function testLogDefaultsToErrorLevel(): void
    {
        $logger = new MemoryLogger();

        $logger->log('test');

        self::assertSame(LogLevel::ERROR, $logger->records[0]->level);
    }

    public function testIncrementsTotalCounter(): void
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
    public function testIncrementsLevelCounter(
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
     * @return \Generator<array{0: LogLevel}>
     */
    public static function logLevelDataProvider(): \Generator
    {
        foreach (LogLevel::cases() as $level) {
            yield [
                $level,
            ];
        }
    }

    #[DataProvider('logLevelDataProvider')]
    public function testGetTotalByLogLevel(
        LogLevel $level,
    ): void {
        $logger = new NullLogger();

        $logger->log(
            message: 'message',
            level: $level,
        );

        self::assertSame(1, $logger->getTotalByLogLevel($level));
    }

    /**
     * @return \Generator<array{0: \Closure(LoggerInterface $logger, \Throwable $exception): LoggerInterface, 1: LogLevel}>
     */
    public static function convenienceMethodDataProvider(): \Generator
    {
        yield [
            static function (LoggerInterface $logger, \Throwable $exception): LoggerInterface {
                return $logger->alert('message', ['key' => 'value'], $exception);
            },
            LogLevel::ALERT,
        ];

        yield [
            static function (LoggerInterface $logger, \Throwable $exception): LoggerInterface {
                return $logger->critical('message', ['key' => 'value'], $exception);
            },
            LogLevel::CRITICAL,
        ];

        yield [
            static function (LoggerInterface $logger, \Throwable $exception): LoggerInterface {
                return $logger->debug('message', ['key' => 'value'], $exception);
            },
            LogLevel::DEBUG,
        ];

        yield [
            static function (LoggerInterface $logger, \Throwable $exception): LoggerInterface {
                return $logger->emergency('message', ['key' => 'value'], $exception);
            },
            LogLevel::EMERGENCY,
        ];

        yield [
            static function (LoggerInterface $logger, \Throwable $exception): LoggerInterface {
                return $logger->error('message', ['key' => 'value'], $exception);
            },
            LogLevel::ERROR,
        ];

        yield [
            static function (LoggerInterface $logger, \Throwable $exception): LoggerInterface {
                return $logger->info('message', ['key' => 'value'], $exception);
            },
            LogLevel::INFO,
        ];

        yield [
            static function (LoggerInterface $logger, \Throwable $exception): LoggerInterface {
                return $logger->notice('message', ['key' => 'value'], $exception);
            },
            LogLevel::NOTICE,
        ];

        yield [
            static function (LoggerInterface $logger, \Throwable $exception): LoggerInterface {
                return $logger->warning('message', ['key' => 'value'], $exception);
            },
            LogLevel::WARNING,
        ];
    }

    /**
     * @param \Closure(LoggerInterface $logger, \Throwable $exception): LoggerInterface $caller
     */
    #[DataProvider('convenienceMethodDataProvider')]
    public function testConvenienceMethodDelegatesToLog(
        \Closure $caller,
        LogLevel $level,
    ): void {
        $exception = new \RuntimeException('failure');
        $logger = new MemoryLogger();

        self::assertSame($logger, $caller($logger, $exception));
        self::assertSame(1, $logger->getTotalByLogLevel($level));
        self::assertSame(1, $logger->total);

        $record = $logger->records[0];

        self::assertSame('message', $record->message);
        self::assertSame(['key' => 'value'], $record->context);
        self::assertSame($level, $record->level);
        self::assertSame($exception, $record->exception);
    }

    public function testTotalReflectsAllLevels(): void
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

    public function testWriteCountsRecord(): void
    {
        $source = new MemoryLogger();
        $logger = new MemoryLogger();

        $source->info('message');
        $logger->write($source->records[0]);

        self::assertSame([$source->records[0]], $logger->records);
        self::assertSame(1, $logger->totalInfo);
    }

    public function testWithContextReturnsChildLogger(): void
    {
        $logger = new MemoryLogger();
        $child = $logger->withContext(
            [
                'requestId' => 'abc',
            ],
        );

        self::assertInstanceOf(ChildLogger::class, $child);
        self::assertNotSame($logger, $child);
    }

    public function testWithContextMergesContextIntoRecords(): void
    {
        $logger = new MemoryLogger();

        $logger
            ->withContext(
                [
                    'requestId' => 'abc',
                    'user' => 'guest',
                ],
            )
            ->withContext(
                [
                    'user' => 'Kalle',
                ],
            )
            ->info(
                'message',
                [
                    'orderId' => 42,
                ],
            );

        self::assertSame(
            [
                'requestId' => 'abc',
                'user' => 'Kalle',
                'orderId' => 42,
            ],
            $logger->records[0]->context,
        );
    }

    public function testCallerContextWinsOverChildContext(): void
    {
        $logger = new MemoryLogger();

        $logger
            ->withContext(
                [
                    'user' => 'guest',
                ],
            )
            ->info(
                'message',
                [
                    'user' => 'Kalle',
                ],
            );

        self::assertSame(['user' => 'Kalle'], $logger->records[0]->context);
    }

    public function testWithContextKeepsChannel(): void
    {
        $logger = new MemoryLogger();

        $logger
            ->withChannel('payments')
            ->withContext(
                [
                    'requestId' => 'abc',
                ],
            )
            ->info('message');

        self::assertSame('payments', $logger->records[0]->channel);
        self::assertSame(['requestId' => 'abc'], $logger->records[0]->context);
    }

    public function testWithChannelSetsChannelAndKeepsContext(): void
    {
        $logger = new MemoryLogger();

        $logger
            ->withContext(
                [
                    'requestId' => 'abc',
                ],
            )
            ->withChannel('payments')
            ->withChannel('refunds')
            ->info('message');

        self::assertSame('refunds', $logger->records[0]->channel);
        self::assertSame(['requestId' => 'abc'], $logger->records[0]->context);
    }

    public function testWithContextDoesNotChangeParent(): void
    {
        $logger = new MemoryLogger();

        $logger
            ->withContext(
                [
                    'requestId' => 'abc',
                ],
            )
            ->info('child');

        $logger->withChannel('payments')->info('channel');
        $logger->info('parent');

        self::assertSame('parent', $logger->records[2]->message);
        self::assertSame([], $logger->records[2]->context);
        self::assertNull($logger->records[2]->channel);
    }

    public function testChildLoggerUsesParentClock(): void
    {
        $clock = $this->createClock();
        $logger = new MemoryLogger(
            clock: $clock,
        );

        $logger->withChannel('payments')->info('message');

        self::assertSame($clock->instant, $logger->records[0]->timestamp);
    }
}
