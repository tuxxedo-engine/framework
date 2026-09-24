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

class TemporalException extends \Exception
{
    public static function fromNanosecondsOutOfRange(
        int $nanoseconds,
    ): self {
        return new self(
            message: \sprintf(
                'Nanoseconds must be in [0, 1000000000); got %d',
                $nanoseconds,
            ),
        );
    }

    public static function fromNegativeMagnitude(
        string $factory,
        int $value,
    ): self {
        return new self(
            message: \sprintf(
                '%s() requires a non-negative value; got %d — use negate() for a negative duration',
                $factory,
                $value,
            ),
        );
    }

    public static function fromDurationOverflow(): self
    {
        return new self(
            message: 'Duration arithmetic overflowed integer range',
        );
    }

    public static function fromDivisionByZero(): self
    {
        return new self(
            message: 'Cannot divide a Duration by zero',
        );
    }

    public static function fromMalformedIso8601Duration(
        string $input,
    ): self {
        return new self(
            message: \sprintf(
                'Malformed ISO 8601 duration specification: "%s"',
                $input,
            ),
        );
    }

    public static function fromIso8601DurationWithDateComponents(
        string $input,
    ): self {
        return new self(
            message: \sprintf(
                'ISO 8601 duration "%s" contains date components; Duration only accepts time components (H, M, S)',
                $input,
            ),
        );
    }

    public static function fromMalformedInstantParse(
        string $input,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot parse "%s" as an Instant',
                $input,
            ),
        );
    }

    public static function fromInvertedPeriod(
        InstantInterface $start,
        InstantInterface $end,
    ): self {
        return new self(
            message: \sprintf(
                'Period end (%s) must not precede start (%s)',
                $end->toIso8601(),
                $start->toIso8601(),
            ),
        );
    }

    public static function fromMalformedTimeZone(
        string $input,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot parse "%s" as a TimeZone',
                $input,
            ),
            previous: $previous,
        );
    }

    public static function fromMalformedLocalDate(
        string $input,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot parse "%s" as a LocalDate',
                $input,
            ),
        );
    }

    public static function fromMalformedLocalTime(
        string $input,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot parse "%s" as a LocalTime',
                $input,
            ),
        );
    }

    public static function fromInvalidLocalDateComponents(
        int $year,
        int $month,
        int $day,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid LocalDate components: %04d-%02d-%02d',
                $year,
                $month,
                $day,
            ),
        );
    }

    public static function fromInvalidLocalTimeComponents(
        int $hour,
        int $minute,
        int $second,
        int $nanosecondFraction,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid LocalTime components: %02d:%02d:%02d.%09d',
                $hour,
                $minute,
                $second,
                $nanosecondFraction,
            ),
        );
    }
}
