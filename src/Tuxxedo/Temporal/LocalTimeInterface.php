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

interface LocalTimeInterface
{
    public int $hour {
        get;
    }

    public int $minute {
        get;
    }

    public int $second {
        get;
    }

    public int $nanosecond {
        get;
    }

    public function equals(
        LocalTimeInterface $other,
    ): bool;

    public function isBefore(
        LocalTimeInterface $other,
    ): bool;

    public function isAfter(
        LocalTimeInterface $other,
    ): bool;

    public function onDate(
        LocalDateInterface $date,
        TimeZoneInterface $timeZone,
    ): InstantInterface;

    public function format(
        string $pattern,
    ): string;

    public function toIso8601(): string;
}
