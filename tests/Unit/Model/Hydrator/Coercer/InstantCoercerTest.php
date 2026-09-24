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
use Tuxxedo\Model\Hydrator\Coercer\InstantCoercer;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\TimeZone;

class InstantCoercerTest extends TestCase
{
    public function testHydrateWithExplicitDateTimeFormatProducesInstant(): void
    {
        $coercer = new InstantCoercer(format: 'Y-m-d H:i:s');

        $instant = $coercer->hydrate(value: '2026-07-16 10:30:45');

        self::assertInstanceOf(InstantInterface::class, $instant);
        self::assertSame('2026-07-16 10:30:45', $instant->format('Y-m-d H:i:s'));
    }

    public function testHydrateWithDateFormatEnumProducesInstant(): void
    {
        $coercer = new InstantCoercer(format: DateFormat::UNIX);

        $instant = $coercer->hydrate(value: '1721126400');

        self::assertInstanceOf(InstantInterface::class, $instant);
        self::assertSame(1721126400, $instant->toUnixTimestamp());
    }

    public function testDehydrateRoundTripsAgainstFormat(): void
    {
        $coercer = new InstantCoercer(format: 'Y-m-d H:i:s');

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: '2026-07-16 10:30:45'),
        );

        self::assertSame('2026-07-16 10:30:45', $rehydrated);
    }

    public function testDehydrateOnInstantProducedElsewhereProducesFormattedString(): void
    {
        $coercer = new InstantCoercer(format: 'Y-m-d');
        $instant = Instant::parse(input: '2026-07-16T00:00:00Z');

        self::assertSame('2026-07-16', $coercer->dehydrate(value: $instant));
    }

    public function testHydrateRejectsNonStringInput(): void
    {
        $coercer = new InstantCoercer();

        $caught = null;

        try {
            $coercer->hydrate(value: 1_721_126_400);
        } catch (ModelException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(ModelException::class, $caught);
    }

    public function testHydrateRejectsStringNotMatchingFormat(): void
    {
        $coercer = new InstantCoercer(format: 'Y-m-d H:i:s');

        $caught = null;

        try {
            $coercer->hydrate(value: 'not a date');
        } catch (ModelException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(ModelException::class, $caught);
    }

    public function testDehydrateRejectsNonInstantValue(): void
    {
        $coercer = new InstantCoercer();

        $caught = null;

        try {
            $coercer->dehydrate(value: 'not an instant');
        } catch (ModelException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(ModelException::class, $caught);
    }

    public function testCustomStringFormatPreservesTimezoneOffset(): void
    {
        $coercer = new InstantCoercer(format: 'Y-m-d\TH:i:sP');
        $original = '2026-07-16T10:30:45+02:00';

        $rehydrated = $coercer->dehydrate(
            value: $coercer->hydrate(value: $original),
        );

        self::assertSame($original, $rehydrated);
    }

    public function testForceTimeZoneConvertsHydratedInstant(): void
    {
        $coercer = new InstantCoercer(
            format: 'Y-m-d\TH:i:sP',
            forceTimeZone: TimeZone::parse(input: 'America/New_York'),
        );

        $instant = $coercer->hydrate(value: '2026-07-16T10:30:45+00:00');

        self::assertSame(
            'America/New_York',
            $instant->toDateTime()->getTimezone()->getName(),
        );
    }

    public function testForceTimeZoneNullPassesThroughFormatZone(): void
    {
        $coercer = new InstantCoercer(format: 'Y-m-d\TH:i:sP');

        $instant = $coercer->hydrate(value: '2026-07-16T10:30:45+02:00');

        self::assertSame(
            '+02:00',
            $instant->toDateTime()->getTimezone()->getName(),
        );
    }
}
