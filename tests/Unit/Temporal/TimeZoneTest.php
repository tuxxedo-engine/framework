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

namespace Unit\Temporal;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Temporal\TemporalException;
use Tuxxedo\Temporal\TimeZone;

class TimeZoneTest extends TestCase
{
    public function testParseAcceptsIanaName(): void
    {
        $zone = TimeZone::parse(input: 'Europe/Copenhagen');

        self::assertSame('Europe/Copenhagen', $zone->name);
    }

    public function testParseRejectsMalformedInput(): void
    {
        $caught = null;

        try {
            TimeZone::parse(input: 'not a zone');
        } catch (TemporalException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(TemporalException::class, $caught);
        self::assertStringContainsString('Cannot parse', $caught->getMessage());
    }

    public function testUtcFactory(): void
    {
        $zone = TimeZone::utc();

        self::assertSame('UTC', $zone->name);
    }

    public function testFromDateTimeZoneWraps(): void
    {
        $native = new \DateTimeZone(timezone: 'America/New_York');
        $zone = TimeZone::fromDateTimeZone(dateTimeZone: $native);

        self::assertSame('America/New_York', $zone->name);
        self::assertSame($native, $zone->dateTimeZone);
    }

    public function testEqualsMatchesOnName(): void
    {
        $a = TimeZone::parse(input: 'UTC');
        $b = TimeZone::utc();

        self::assertTrue($a->equals(other: $b));
    }

    public function testEqualsRejectsDifferentZone(): void
    {
        self::assertFalse(
            TimeZone::parse(input: 'UTC')->equals(
                other: TimeZone::parse(input: 'Europe/Copenhagen'),
            ),
        );
    }
}
