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

interface LocalDateInterface
{
    public int $year {
        get;
    }

    public Month $month {
        get;
    }

    public int $day {
        get;
    }

    public function equals(
        LocalDateInterface $other,
    ): bool;

    public function isBefore(
        LocalDateInterface $other,
    ): bool;

    public function isAfter(
        LocalDateInterface $other,
    ): bool;

    public function atStartOfDay(
        TimeZoneInterface $timeZone,
    ): InstantInterface;

    public function format(
        string $pattern,
    ): string;

    public function toIso8601(): string;

    public function plusDays(
        int $days,
    ): self;

    public function plusMonths(
        int $months,
    ): self;

    public function plusYears(
        int $years,
    ): self;

    public function minusDays(
        int $days,
    ): self;

    public function minusMonths(
        int $months,
    ): self;

    public function minusYears(
        int $years,
    ): self;
}
