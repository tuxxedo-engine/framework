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
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\NullLogger;

class NullLoggerTest extends TestCase
{
    public function testWriteOnlyCounts(): void
    {
        $logger = new NullLogger();

        $logger->info('message');
        $logger->error('message');

        self::assertSame(2, $logger->total);
    }

    public function testIsDefaultImplementation(): void
    {
        self::assertInstanceOf(
            NullLogger::class,
            (new Container())->resolve(LoggerInterface::class),
        );
    }
}
