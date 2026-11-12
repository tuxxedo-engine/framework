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
use Support\Logger\Processor\ExtraProcessor;
use Tuxxedo\Container\Container;
use Tuxxedo\Logger\Config\StreamLoggerConfig;
use Tuxxedo\Logger\Formatter\LogFormat;
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\LoggerException;
use Tuxxedo\Logger\StreamLogger;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class StreamLoggerConfigTest extends TestCase
{
    private function createContainer(): Container
    {
        $container = new Container();

        $container->singleton(
            new FixedClock(
                instant: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        return $container;
    }

    private function createFile(
        string $contents = '',
    ): string {
        $file = \tempnam(\sys_get_temp_dir(), 'tuxxedo_test_');

        self::assertIsString($file);

        \file_put_contents($file, $contents);

        return $file;
    }

    private function readFile(
        string $file,
    ): string {
        $contents = \file_get_contents($file);

        self::assertIsString($contents);

        return $contents;
    }

    public function testDefaults(): void
    {
        $config = new StreamLoggerConfig(
            file: 'php://stderr',
        );

        self::assertSame('php://stderr', $config->file);
        self::assertTrue($config->append);
        self::assertTrue($config->autoFlush);
        self::assertSame(LogFormat::LINE, $config->format);
        self::assertSame(LogFailureMode::MARKER, $config->unencodable);
    }

    public function testCreatesLineLogger(): void
    {
        $file = $this->createFile('existing' . \PHP_EOL);

        try {
            $logger = (new StreamLoggerConfig(
                file: $file,
            ))->createLogger($this->createContainer());

            self::assertInstanceOf(StreamLogger::class, $logger);
            self::assertTrue($logger->autoFlush);

            $logger->info('Hello');

            self::assertSame(
                'existing' . \PHP_EOL . '[2026-01-01T12:00:00.000000+00:00] [INFO] Hello' . \PHP_EOL,
                $this->readFile($file),
            );
        } finally {
            \unlink($file);
        }
    }

    public function testCreatesJsonLoggerWithoutAppending(): void
    {
        $file = $this->createFile('existing' . \PHP_EOL);

        try {
            $logger = (new StreamLoggerConfig(
                file: $file,
                append: false,
                autoFlush: false,
                format: LogFormat::JSON,
                unencodable: LogFailureMode::OMIT,
            ))->createLogger($this->createContainer());

            self::assertInstanceOf(StreamLogger::class, $logger);
            self::assertFalse($logger->autoFlush);

            $logger->info(
                'Hello',
                [
                    'ratio' => \NAN,
                ],
            );

            unset($logger);

            self::assertSame(
                '{"timestamp":"2026-01-01T12:00:00.000000+00:00","level":"INFO","channel":null,"message":"Hello","message_template":"Hello","context":[],"extra":[],"exception":null}' . "\n",
                $this->readFile($file),
            );
        } finally {
            \unlink($file);
        }
    }

    public function testThrowsOnUnwritableFile(): void
    {
        self::expectException(LoggerException::class);

        (new StreamLoggerConfig(
            file: '/nonexistent/path/to/file.log',
        ))->createLogger($this->createContainer());
    }

    public function testPassesProcessorsAndFailureMode(): void
    {
        $file = $this->createFile();

        try {
            $logger = (new StreamLoggerConfig(
                file: $file,
                format: LogFormat::JSON,
                processors: [
                    ExtraProcessor::class,
                ],
                processorFailure: LogFailureMode::OMIT,
            ))->createLogger($this->createContainer());

            self::assertInstanceOf(StreamLogger::class, $logger);
            self::assertSame(LogFailureMode::OMIT, $logger->processorFailure);

            $logger->info('Hello');

            self::assertStringContainsString('"extra":{"support.key":"value"}', $this->readFile($file));
        } finally {
            \unlink($file);
        }
    }
}
