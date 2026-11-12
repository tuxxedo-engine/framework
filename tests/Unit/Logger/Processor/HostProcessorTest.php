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
use Tuxxedo\Logger\Processor\HostProcessor;
use Tuxxedo\Logger\Processor\Info\HostInfoInterface;
use Tuxxedo\Logger\Processor\Info\ProcessInfoInterface;
use Tuxxedo\Temporal\Instant;

class HostProcessorTest extends TestCase
{
    public function testAddsHostAndProcessInfo(): void
    {
        $record = (new HostProcessor())->process(
            new Container(),
            new LogRecord(
                message: 'message',
                level: LogLevel::INFO,
                timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        self::assertSame(
            [
                'host',
                'process',
            ],
            \array_keys($record->extra),
        );

        $host = $record->extra['host'];
        $process = $record->extra['process'];
        $name = \gethostname();
        $pid = \getmypid();

        self::assertInstanceOf(HostInfoInterface::class, $host);
        self::assertSame($name !== false ? $name : null, $host->name);
        self::assertSame(\PHP_OS_FAMILY, $host->os->family);
        self::assertSame(\php_uname('s'), $host->os->name);
        self::assertSame(\php_uname('r'), $host->os->release);
        self::assertSame(\php_uname('v'), $host->os->version);
        self::assertSame(\php_uname('m'), $host->arch);

        self::assertInstanceOf(ProcessInfoInterface::class, $process);
        self::assertSame($pid !== false ? $pid : null, $process->pid);
    }
}
