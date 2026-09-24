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
use Tuxxedo\Temporal\StaticTimeZoneResolver;
use Tuxxedo\Temporal\TimeZone;

class StaticTimeZoneResolverTest extends TestCase
{
    public function testCurrentTimeZoneReturnsConstructorArgument(): void
    {
        $zone = TimeZone::parse(input: 'Europe/Copenhagen');
        $resolver = new StaticTimeZoneResolver(timeZone: $zone);

        self::assertSame($zone, $resolver->currentTimeZone());
    }

    public function testResolverReturnsSameInstanceAcrossCalls(): void
    {
        $resolver = new StaticTimeZoneResolver(timeZone: TimeZone::utc());

        self::assertSame($resolver->currentTimeZone(), $resolver->currentTimeZone());
    }
}
