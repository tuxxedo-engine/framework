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

class LogLevelTest extends TestCase
{
    /**
     * @return \Generator<array{0: LogLevel, 1: int}>
     */
    public static function severityDataProvider(): \Generator
    {
        yield [
            LogLevel::EMERGENCY,
            0,
        ];

        yield [
            LogLevel::ALERT,
            1,
        ];

        yield [
            LogLevel::CRITICAL,
            2,
        ];

        yield [
            LogLevel::ERROR,
            3,
        ];

        yield [
            LogLevel::WARNING,
            4,
        ];

        yield [
            LogLevel::NOTICE,
            5,
        ];

        yield [
            LogLevel::INFO,
            6,
        ];

        yield [
            LogLevel::DEBUG,
            7,
        ];
    }

    #[DataProvider('severityDataProvider')]
    public function testSeverityValues(
        LogLevel $level,
        int $severity,
    ): void {
        self::assertSame($severity, $level->value);
    }

    /**
     * @return \Generator<array{0: LogLevel, 1: LogLevel, 2: bool}>
     */
    public static function isAtLeastDataProvider(): \Generator
    {
        yield [
            LogLevel::ERROR,
            LogLevel::WARNING,
            true,
        ];

        yield [
            LogLevel::WARNING,
            LogLevel::WARNING,
            true,
        ];

        yield [
            LogLevel::INFO,
            LogLevel::WARNING,
            false,
        ];

        yield [
            LogLevel::EMERGENCY,
            LogLevel::DEBUG,
            true,
        ];

        yield [
            LogLevel::DEBUG,
            LogLevel::EMERGENCY,
            false,
        ];
    }

    #[DataProvider('isAtLeastDataProvider')]
    public function testIsAtLeast(
        LogLevel $level,
        LogLevel $minimum,
        bool $expected,
    ): void {
        self::assertSame($expected, $level->isAtLeast($minimum));
    }
}
