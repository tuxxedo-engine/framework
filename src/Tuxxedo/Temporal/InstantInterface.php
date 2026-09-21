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

interface InstantInterface
{
    public \DateTimeImmutable $dateTime {
        get;
    }

    public int $nanosecondFraction {
        get;
    }

    public function plus(
        DurationInterface $duration,
    ): self;

    public function minus(
        DurationInterface $duration,
    ): self;

    public function difference(
        InstantInterface $other,
    ): DurationInterface;

    public function isBefore(
        InstantInterface $other,
    ): bool;

    public function isAfter(
        InstantInterface $other,
    ): bool;

    public function equals(
        InstantInterface $other,
    ): bool;

    public function format(
        string $pattern,
    ): string;

    public function toIso8601(): string;

    public function toRfc3339(): string;

    public function toAtom(): string;

    public function toUnixTimestamp(): int;

    public function toDateTime(): \DateTimeImmutable;

    public function dayOfWeek(): DayOfWeek;

    public function month(): Month;
}
