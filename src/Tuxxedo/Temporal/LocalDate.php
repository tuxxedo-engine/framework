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

class LocalDate implements LocalDateInterface
{
    public Month $month {
        get {
            return Month::from(value: $this->monthNumber);
        }
    }

    private function __construct(
        public readonly int $year,
        private readonly int $monthNumber,
        public readonly int $day,
    ) {
    }

    public static function of(
        int $year,
        int $month,
        int $day,
    ): self {
        if (!self::isValid(year: $year, month: $month, day: $day)) {
            throw TemporalException::fromInvalidLocalDateComponents(
                year: $year,
                month: $month,
                day: $day,
            );
        }

        return new self(
            year: $year,
            monthNumber: $month,
            day: $day,
        );
    }

    public static function parse(
        string $input,
    ): self {
        if (\preg_match(pattern: '/^(-?\d{4,})-(\d{2})-(\d{2})$/', subject: $input, matches: $matches) !== 1) {
            throw TemporalException::fromMalformedLocalDate(input: $input);
        }

        return self::of(
            year: (int) $matches[1],
            month: (int) $matches[2],
            day: (int) $matches[3],
        );
    }

    public static function fromInstant(
        InstantInterface $instant,
        TimeZoneInterface $timeZone,
    ): self {
        $shifted = $instant->dateTime->setTimezone(timezone: $timeZone->dateTimeZone);

        return new self(
            year: (int) $shifted->format(format: 'Y'),
            monthNumber: (int) $shifted->format(format: 'n'),
            day: (int) $shifted->format(format: 'j'),
        );
    }

    public function equals(
        LocalDateInterface $other,
    ): bool {
        return $this->year === $other->year &&
            $this->monthNumber === $other->month->value &&
            $this->day === $other->day;
    }

    public function isBefore(
        LocalDateInterface $other,
    ): bool {
        return $this->compareTo(other: $other) < 0;
    }

    public function isAfter(
        LocalDateInterface $other,
    ): bool {
        return $this->compareTo(other: $other) > 0;
    }

    public function atStartOfDay(
        TimeZoneInterface $timeZone,
    ): InstantInterface {
        $dateTime = new \DateTimeImmutable(
            datetime: $this->toIso8601() . 'T00:00:00',
            timezone: $timeZone->dateTimeZone,
        );

        return Instant::fromDateTime(dateTime: $dateTime);
    }

    public function format(
        string $pattern,
    ): string {
        return $this->toDateTimeImmutable()->format($pattern);
    }

    public function toIso8601(): string
    {
        return \sprintf(
            '%04d-%02d-%02d',
            $this->year,
            $this->monthNumber,
            $this->day,
        );
    }

    public function plusDays(
        int $days,
    ): self {
        return $this->modify(spec: $days . ' days');
    }

    public function plusMonths(
        int $months,
    ): self {
        return $this->modify(spec: $months . ' months');
    }

    public function plusYears(
        int $years,
    ): self {
        return $this->modify(spec: $years . ' years');
    }

    public function minusDays(
        int $days,
    ): self {
        return $this->plusDays(days: -$days);
    }

    public function minusMonths(
        int $months,
    ): self {
        return $this->plusMonths(months: -$months);
    }

    public function minusYears(
        int $years,
    ): self {
        return $this->plusYears(years: -$years);
    }

    private function modify(
        string $spec,
    ): self {
        $shifted = $this->toDateTimeImmutable()->modify($spec);

        return new self(
            year: (int) $shifted->format(format: 'Y'),
            monthNumber: (int) $shifted->format(format: 'n'),
            day: (int) $shifted->format(format: 'j'),
        );
    }

    private function compareTo(
        LocalDateInterface $other,
    ): int {
        $yearComparison = $this->year <=> $other->year;

        if ($yearComparison !== 0) {
            return $yearComparison;
        }

        $monthComparison = $this->monthNumber <=> $other->month->value;

        if ($monthComparison !== 0) {
            return $monthComparison;
        }

        return $this->day <=> $other->day;
    }

    private function toDateTimeImmutable(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(
            datetime: $this->toIso8601() . 'T00:00:00',
            timezone: new \DateTimeZone(timezone: 'UTC'),
        );
    }

    private static function isValid(
        int $year,
        int $month,
        int $day,
    ): bool {
        if ($month < 1 || $month > 12) {
            return false;
        }

        if ($day < 1) {
            return false;
        }

        $monthEnum = Month::from(value: $month);

        return $day <= $monthEnum->lengthInDays(year: $year);
    }
}
