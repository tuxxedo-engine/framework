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
use Tuxxedo\Model\Hydrator\Coercer\DateTimeCoercer;

class DateTimeCoercerTest extends TestCase
{
    public function testRoundTripWithExplicitDateTimeFormat(): void
    {
        $coercer = new DateTimeCoercer(format: 'Y-m-d H:i:s');
        $original = '2026-07-16 10:30:45';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: $original),
        );

        self::assertSame(
            $original,
            $rehydrated,
        );
    }

    public function testCustomFormatWithTimezoneRoundTrips(): void
    {
        $coercer = new DateTimeCoercer(format: 'Y-m-d\TH:i:sP');
        $original = '2026-07-16T10:30:45+02:00';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: $original),
        );

        self::assertSame(
            $original,
            $rehydrated,
        );
    }
}
