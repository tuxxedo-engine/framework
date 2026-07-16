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

namespace Unit\Model\Hydrator\Coercer;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Attribute\Column\TimeFormat;
use Tuxxedo\Model\Hydrator\Coercer\TimeCoercer;

class TimeCoercerTest extends TestCase
{
    public function testRoundTripDefaultFormat(): void
    {
        $coercer = new TimeCoercer();
        $original = '10:30:45';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: $original),
        );

        self::assertSame(
            $original,
            $rehydrated,
        );
    }

    public function testTwelveHourFormatRoundTrips(): void
    {
        $coercer = new TimeCoercer(format: TimeFormat::TWELVE);
        $original = '10:30:45 PM';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: $original),
        );

        self::assertSame(
            $original,
            $rehydrated,
        );
    }
}
