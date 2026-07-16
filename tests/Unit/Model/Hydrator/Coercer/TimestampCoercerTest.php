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
use Tuxxedo\Model\Attribute\Column\DateFormat;
use Tuxxedo\Model\Hydrator\Coercer\TimestampCoercer;

class TimestampCoercerTest extends TestCase
{
    public function testRoundTripUnixFormat(): void
    {
        $coercer = new TimestampCoercer(format: DateFormat::UNIX);
        $original = '1721126400';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: $original),
        );

        self::assertSame(
            $original,
            $rehydrated,
        );
    }

    public function testRoundTripCustomStringFormat(): void
    {
        $coercer = new TimestampCoercer(format: 'Y-m-d H:i:s');
        $original = '2026-07-16 10:30:45';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: $original),
        );

        self::assertSame(
            $original,
            $rehydrated,
        );
    }
}
