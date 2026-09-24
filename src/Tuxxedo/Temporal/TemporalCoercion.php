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
        mixed $value,
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

        /** @var string $value */

        return Instant::parse($value);
    }

    public static function format(
        mixed $value,
        string $pattern,
    ): string {
        if (
            $value instanceof InstantInterface ||
            $value instanceof LocalDateInterface ||
            $value instanceof LocalTimeInterface
        ) {
            return $value->format($pattern);
        }

        return self::toInstant($value)->format($pattern);
    }
}
