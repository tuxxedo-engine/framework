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
use Tuxxedo\Logger\FilterLogger;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\MemoryLogger;

class FilterLoggerTest extends TestCase
{
    public function testPassesRecordsAtOrAboveMinimum(): void
    {
        $inner = new MemoryLogger();
        $logger = new FilterLogger(
            minimum: LogLevel::WARNING,
            logger: $inner,
        );

        $logger->debug('debug');
        $logger->info('info');
        $logger->warning('warning');
        $logger->error('error');
        $logger->emergency('emergency');

        self::assertSame(
            [
                'warning',
                'error',
                'emergency',
            ],
            \array_map(
                static fn (LogRecordInterface $record): string => $record->message,
                $inner->records,
            ),
        );
    }

    public function testCountsOnlyPassedRecords(): void
    {
        $inner = new MemoryLogger();
        $logger = new FilterLogger(
            minimum: LogLevel::ERROR,
            logger: $inner,
        );

        $logger->info('info');
        $logger->error('error');

        self::assertSame(1, $logger->total);
        self::assertSame(0, $logger->totalInfo);
        self::assertSame(1, $logger->totalError);
        self::assertSame(1, $inner->total);
    }
}
