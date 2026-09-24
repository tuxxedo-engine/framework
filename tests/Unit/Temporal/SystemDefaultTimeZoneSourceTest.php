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
use Tuxxedo\Temporal\SourcePriority;
use Tuxxedo\Temporal\SystemDefaultTimeZoneSource;
use Tuxxedo\Temporal\TimeZone;

class SystemDefaultTimeZoneSourceTest extends TestCase
{
    public function testDetectReturnsDefault(): void
    {
        $zone = TimeZone::parse(input: 'Europe/Copenhagen');
        $source = new SystemDefaultTimeZoneSource(default: $zone);

        self::assertSame($zone, $source->detect());
    }

    public function testPriorityIsLowest(): void
    {
        $source = new SystemDefaultTimeZoneSource(default: TimeZone::utc());

        self::assertSame(SourcePriority::LOWEST, $source->priority);
    }
}
