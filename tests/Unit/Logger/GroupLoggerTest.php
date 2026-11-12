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
use Tuxxedo\Logger\FilterLogger;
use Tuxxedo\Logger\GroupLogger;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\MemoryLogger;

class GroupLoggerTest extends TestCase
{
    public function testWritesSameRecordToEveryLogger(): void
    {
        $first = new MemoryLogger(
            container: new Container(),
        );
        $second = new MemoryLogger(
            container: new Container(),
        );
        $logger = new GroupLogger(
            container: new Container(),
            loggers: [
                $first,
                $second,
            ],
        );

        $logger->info('message');

        self::assertSame(1, $logger->total);
        self::assertCount(1, $first);
        self::assertCount(1, $second);
        self::assertSame($first->records[0], $second->records[0]);
    }

    public function testFilteredLoggerInsideGroup(): void
    {
        $all = new MemoryLogger(
            container: new Container(),
        );
        $errors = new MemoryLogger(
            container: new Container(),
        );
        $logger = new GroupLogger(
            container: new Container(),
            loggers: [
                $all,
                new FilterLogger(
                    container: new Container(),
                    minimum: LogLevel::ERROR,
                    logger: $errors,
                ),
            ],
        );

        $logger->info('info');
        $logger->error('error');

        self::assertSame(2, $logger->total);
        self::assertCount(2, $all);
        self::assertCount(1, $errors);
        self::assertSame('error', $errors->records[0]->message);
    }

    public function testEmptyGroupOnlyCounts(): void
    {
        $logger = new GroupLogger(
            container: new Container(),
            loggers: [],
        );

        $logger->info('message');

        self::assertSame(1, $logger->total);
    }
}
