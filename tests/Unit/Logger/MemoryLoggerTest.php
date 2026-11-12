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
use Tuxxedo\Container\Container;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\MemoryLogger;

class MemoryLoggerTest extends TestCase
{
    public function testStartsEmpty(): void
    {
        $logger = new MemoryLogger(
            container: new Container(),
        );

        self::assertSame([], $logger->records);
        self::assertCount(0, $logger);
    }

    public function testKeepsRecordsInOrder(): void
    {
        $logger = new MemoryLogger(
            container: new Container(),
        );

        $logger->info('first');
        $logger->warning('second');

        self::assertCount(2, $logger);
        self::assertSame('first', $logger->records[0]->message);
        self::assertSame(LogLevel::INFO, $logger->records[0]->level);
        self::assertSame('second', $logger->records[1]->message);
        self::assertSame(LogLevel::WARNING, $logger->records[1]->level);
    }
}
