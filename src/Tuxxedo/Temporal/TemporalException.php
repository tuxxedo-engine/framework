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
}
