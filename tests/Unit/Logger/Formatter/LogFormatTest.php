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

namespace Unit\Logger\Formatter;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Logger\Formatter\JsonFormatter;
use Tuxxedo\Logger\Formatter\LineFormatter;
use Tuxxedo\Logger\Formatter\LogFormat;
use Tuxxedo\Logger\LogFailureMode;

class LogFormatTest extends TestCase
{
    public function testLineCreatesLineFormatter(): void
    {
        self::assertInstanceOf(LineFormatter::class, LogFormat::LINE->createFormatter());
    }

    public function testJsonCreatesJsonFormatterWithMarkerByDefault(): void
    {
        $formatter = LogFormat::JSON->createFormatter();

        self::assertInstanceOf(JsonFormatter::class, $formatter);
        self::assertSame(LogFailureMode::MARKER, $formatter->unencodable);
    }

    public function testJsonCreatesJsonFormatterWithGivenMode(): void
    {
        $formatter = LogFormat::JSON->createFormatter(
            unencodable: LogFailureMode::OMIT,
        );

        self::assertInstanceOf(JsonFormatter::class, $formatter);
        self::assertSame(LogFailureMode::OMIT, $formatter->unencodable);
    }
}
