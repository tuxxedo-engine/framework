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

class Period implements PeriodInterface
{
    private function __construct(
        public readonly InstantInterface $start,
        public readonly InstantInterface $end,
    ) {
    }

    public static function of(
        InstantInterface $start,
        InstantInterface $end,
    ): self {
        if ($end->isBefore(other: $start)) {
            throw TemporalException::fromInvertedPeriod(
                start: $start,
                end: $end,
            );
        }

        return new self(
            start: $start,
            end: $end,
        );
    }

    public static function fromDuration(
        InstantInterface $start,
        DurationInterface $duration,
    ): self {
        if ($duration->isNegative()) {
            throw TemporalException::fromInvertedPeriod(
                start: $start,
                end: $start->plus(duration: $duration),
            );
        }

        return new self(
            start: $start,
            end: $start->plus(duration: $duration),
        );
    }

    public function contains(
        InstantInterface $instant,
    ): bool {
        if ($instant->isBefore(other: $this->start)) {
            return false;
        }

        if ($instant->isAfter(other: $this->end)) {
            return false;
        }

        return true;
    }

    public function overlaps(
        PeriodInterface $other,
    ): bool {
        if ($this->end->isBefore(other: $other->start)) {
            return false;
        }

        if ($other->end->isBefore(other: $this->start)) {
            return false;
        }

        return true;
    }

    public function length(): DurationInterface
    {
        return $this->end->difference(other: $this->start);
    }
}
