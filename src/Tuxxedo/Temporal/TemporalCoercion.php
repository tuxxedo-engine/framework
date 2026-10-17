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

namespace Tuxxedo\Temporal;

class TemporalCoercion
{
    public static function toInstant(
        InstantInterface|\DateTimeInterface|int|string $value,
    ): InstantInterface {
        if ($value instanceof InstantInterface) {
            return $value;
        }

        if ($value instanceof \DateTimeImmutable) {
            return Instant::fromDateTime($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return Instant::fromDateTime(\DateTimeImmutable::createFromInterface($value));
        }

        if (\is_int($value)) {
            return Instant::fromUnixTimestamp($value);
        }

        return Instant::parse($value);
    }

    public static function toDateTime(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        ?TimeZoneInterface $timeZone = null,
    ): \DateTimeImmutable {
        if ($value instanceof LocalDateInterface) {
            return $value->atStartOfDay(TimeZone::utc())->toDateTime();
        }

        if ($value instanceof LocalTimeInterface) {
            return $value->onDate(
                date: LocalDate::of(
                    year: 1970,
                    month: 1,
                    day: 1,
                ),
                timeZone: TimeZone::utc(),
            )->toDateTime();
        }

        return self::toZonedInstant(
            value: $value,
            timeZone: $timeZone,
        )->toDateTime();
    }

    public static function format(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        string $pattern,
        ?TimeZoneInterface $timeZone = null,
    ): string {
        if (
            $value instanceof LocalDateInterface ||
            $value instanceof LocalTimeInterface
        ) {
            return $value->format($pattern);
        }

        return self::toZonedInstant(
            value: $value,
            timeZone: $timeZone,
        )->format($pattern);
    }

    private static function toZonedInstant(
        InstantInterface|\DateTimeInterface|int|string $value,
        ?TimeZoneInterface $timeZone,
    ): InstantInterface {
        $instant = self::toInstant($value);

        return $timeZone !== null
            ? $instant->withTimeZone($timeZone)
            : $instant;
    }
}
