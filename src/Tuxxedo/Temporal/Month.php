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

enum Month: int
{
    case JANUARY = 1;
    case FEBRUARY = 2;
    case MARCH = 3;
    case APRIL = 4;
    case MAY = 5;
    case JUNE = 6;
    case JULY = 7;
    case AUGUST = 8;
    case SEPTEMBER = 9;
    case OCTOBER = 10;
    case NOVEMBER = 11;
    case DECEMBER = 12;

    public static function fromInstant(
        InstantInterface $instant,
    ): self {
        return self::from(
            value: (int) $instant->dateTime->format(format: 'n'),
        );
    }

    public function lengthInDays(
        int $year,
    ): int {
        return match ($this) {
            self::JANUARY,
            self::MARCH,
            self::MAY,
            self::JULY,
            self::AUGUST,
            self::OCTOBER,
            self::DECEMBER => 31,
            self::APRIL,
            self::JUNE,
            self::SEPTEMBER,
            self::NOVEMBER => 30,
            self::FEBRUARY => self::isLeapYear(year: $year)
                ? 29
                : 28,
        };
    }

    private static function isLeapYear(
        int $year,
    ): bool {
        if ($year % 4 !== 0) {
            return false;
        }

        if ($year % 100 !== 0) {
            return true;
        }

        return $year % 400 === 0;
    }
}
