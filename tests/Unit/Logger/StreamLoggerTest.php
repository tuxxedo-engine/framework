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
use Tuxxedo\Logger\LoggerException;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\StreamLogger;

class StreamLoggerTest extends TestCase
{
    /**
     * @return resource
     */
    private function createMemoryStream(): mixed
    {
        $stream = \fopen('php://memory', 'r+');

        self::assertIsResource($stream);

        return $stream;
    }

    /**
     * @param resource $stream
     */
    private function readStream(
        mixed $stream,
    ): string {
        \rewind($stream);

        return \stream_get_contents($stream);
    }

    public function testConstructWithValidResource(): void
    {
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger($stream);

        self::assertSame(0, $logger->total);
    }

    public function testCreateFromFile(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'tuxxedo_test_');

        self::assertIsString($file);

        try {
            $logger = StreamLogger::createFromFile($file);

            self::assertInstanceOf(StreamLogger::class, $logger);
        } finally {
            \unlink($file);
        }
    }

    public function testCreateFromFileThrowsOnUnwritablePath(): void
    {
        self::expectException(LoggerException::class);

        StreamLogger::createFromFile('/nonexistent/path/to/file.log');
    }

    public function testCreateFromFileAppendMode(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'tuxxedo_test_');

        self::assertIsString($file);

        try {
            \file_put_contents($file, 'existing content' . \PHP_EOL);

            $logger = StreamLogger::createFromFile($file);

            $logger->log('new entry');

            $contents = ($contents = \file_get_contents($file)) !== false
                ? $contents
                : '';

            self::assertStringContainsString('existing content', $contents);
            self::assertStringContainsString('new entry', $contents);
        } finally {
            \unlink($file);
        }
    }

    public function testCreateFromFileWriteMode(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'tuxxedo_test_');

        self::assertIsString($file);

        try {
            \file_put_contents($file, 'existing content' . \PHP_EOL);

            $logger = StreamLogger::createFromFile(
                file: $file,
                append: false,
            );

            $logger->log('new entry');

            $contents = ($contents = \file_get_contents($file)) !== false
                ? $contents
                : '';

            self::assertStringNotContainsString('existing content', $contents);
            self::assertStringContainsString('new entry', $contents);
        } finally {
            \unlink($file);
        }
    }

    public function testLogWritesToStream(): void
    {
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger($stream);

        $logger->log('Hello Engine');

        $output = $this->readStream($stream);

        self::assertStringContainsString('Hello Engine', $output);
    }

    public function testLogWritesLevelToStream(): void
    {
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger($stream);

        $logger->log(
            message: 'test',
            level: LogLevel::WARNING,
        );

        $output = $this->readStream($stream);

        self::assertStringContainsString('[WARNING]', $output);
        self::assertStringContainsString('test', $output);
    }

    public function testLogInterpolatesPlaceholders(): void
    {
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger($stream);

        $logger->log(
            message: 'User {name} logged in',
            placeholders: [
                'name' => 'Kalle',
            ],
        );

        $output = $this->readStream($stream);

        self::assertStringContainsString('User Kalle logged in', $output);
        self::assertStringNotContainsString('{name}', $output);
    }

    public function testLogReturnsSelf(): void
    {
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger($stream);

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
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger($stream);

        $logger->log(
            message: 'message',
            level: $level,
        );

        self::assertSame(1, $propertyReader($logger));
        self::assertSame(1, $logger->total);
    }

    public function testMultipleCallsAppendToStream(): void
    {
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger($stream);

        $logger->log('first');
        $logger->log('second');
        $logger->log('third');

        $output = $this->readStream($stream);

        self::assertStringContainsString('first', $output);
        self::assertStringContainsString('second', $output);
        self::assertStringContainsString('third', $output);
        self::assertSame(3, $logger->total);
    }

    public function testAutoFlushDefault(): void
    {
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger($stream);

        self::assertTrue($logger->autoFlush);
    }

    public function testAutoFlushCanBeDisabled(): void
    {
        $stream = $this->createMemoryStream();
        $logger = new StreamLogger(
            stream: $stream,
            autoFlush: false,
        );

        self::assertFalse($logger->autoFlush);

        $logger->log('message');

        $output = $this->readStream($stream);

        self::assertStringContainsString('message', $output);
    }
}
