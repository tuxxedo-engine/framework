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

class LocalTime implements LocalTimeInterface
{
    private const int NANOS_PER_SECOND = 1_000_000_000;

    private const int NANOS_PER_MICROSECOND = 1_000;

    private function __construct(
        public readonly int $hour,
        public readonly int $minute,
        public readonly int $second,
        public readonly int $nanosecond,
    ) {
    }

    public static function of(
        int $hour,
        int $minute = 0,
        int $second = 0,
        int $nanosecond = 0,
    ): self {
        if (!self::isValid(
            hour: $hour,
            minute: $minute,
            second: $second,
            nanosecond: $nanosecond,
        )) {
            throw TemporalException::fromInvalidLocalTimeComponents(
                hour: $hour,
                minute: $minute,
                second: $second,
                nanosecondFraction: $nanosecond,
            );
        }

        return new self(
            hour: $hour,
            minute: $minute,
            second: $second,
            nanosecond: $nanosecond,
        );
    }

    public static function midnight(): self
    {
        return new self(
            hour: 0,
            minute: 0,
            second: 0,
            nanosecond: 0,
        );
    }

    public static function noon(): self
    {
        return new self(
            hour: 12,
            minute: 0,
            second: 0,
            nanosecond: 0,
        );
    }

    public static function parse(
        string $input,
    ): self {
        if (\preg_match(
            pattern: '/^(\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,9}))?$/',
            subject: $input,
            matches: $matches,
        ) !== 1) {
            throw TemporalException::fromMalformedLocalTime(input: $input);
        }

        $nanosecond = 0;

        if (($matches[4] ?? '') !== '') {
            $padded = \str_pad(
                string: $matches[4],
                length: 9,
                pad_string: '0',
                pad_type: \STR_PAD_RIGHT,
            );
            $nanosecond = (int) $padded;
        }

        return self::of(
            hour: (int) $matches[1],
            minute: (int) $matches[2],
            second: (int) $matches[3],
            nanosecond: $nanosecond,
        );
    }

    public static function fromInstant(
        InstantInterface $instant,
        TimeZoneInterface $timeZone,
    ): self {
        $shifted = $instant->dateTime->setTimezone(timezone: $timeZone->dateTimeZone);
        $microseconds = (int) $shifted->format(format: 'u');

        return new self(
            hour: (int) $shifted->format(format: 'G'),
            minute: (int) $shifted->format(format: 'i'),
            second: (int) $shifted->format(format: 's'),
            nanosecond: $microseconds * self::NANOS_PER_MICROSECOND + $instant->nanosecondFraction,
        );
    }

    public function equals(
        LocalTimeInterface $other,
    ): bool {
        return $this->hour === $other->hour &&
            $this->minute === $other->minute &&
            $this->second === $other->second &&
            $this->nanosecond === $other->nanosecond;
    }

    public function isBefore(
        LocalTimeInterface $other,
    ): bool {
        return $this->compareTo(other: $other) < 0;
    }

    public function isAfter(
        LocalTimeInterface $other,
    ): bool {
        return $this->compareTo(other: $other) > 0;
    }

    public function onDate(
        LocalDateInterface $date,
        TimeZoneInterface $timeZone,
    ): InstantInterface {
        $microseconds = \intdiv($this->nanosecond, self::NANOS_PER_MICROSECOND);
        $dateTime = \DateTimeImmutable::createFromFormat(
            format: 'Y-m-d H:i:s.u',
            datetime: \sprintf(
                '%s %02d:%02d:%02d.%06d',
                $date->toIso8601(),
                $this->hour,
                $this->minute,
                $this->second,
                $microseconds,
            ),
            timezone: $timeZone->dateTimeZone,
        );

        if ($dateTime === false) {
            throw TemporalException::fromInvalidLocalTimeComponents( // @codeCoverageIgnore
                hour: $this->hour,                                    // @codeCoverageIgnore
                minute: $this->minute,                                // @codeCoverageIgnore
                second: $this->second,                                // @codeCoverageIgnore
                nanosecondFraction: $this->nanosecond,                // @codeCoverageIgnore
            );                                                        // @codeCoverageIgnore
        }

        return Instant::fromDateTime(dateTime: $dateTime);
    }

    public function format(
        string $pattern,
    ): string {
        $today = new \DateTimeImmutable(
            datetime: \sprintf(
                '2000-01-01T%02d:%02d:%02d',
                $this->hour,
                $this->minute,
                $this->second,
            ),
            timezone: new \DateTimeZone(timezone: 'UTC'),
        );

        return $today->format($pattern);
    }

    public function toIso8601(): string
    {
        if ($this->nanosecond === 0) {
            return \sprintf(
                '%02d:%02d:%02d',
                $this->hour,
                $this->minute,
                $this->second,
            );
        }

        return \sprintf(
            '%02d:%02d:%02d.%09d',
            $this->hour,
            $this->minute,
            $this->second,
            $this->nanosecond,
        );
    }

    private function compareTo(
        LocalTimeInterface $other,
    ): int {
        $hourComparison = $this->hour <=> $other->hour;

        if ($hourComparison !== 0) {
            return $hourComparison;
        }

        $minuteComparison = $this->minute <=> $other->minute;

        if ($minuteComparison !== 0) {
            return $minuteComparison;
        }

        $secondComparison = $this->second <=> $other->second;

        if ($secondComparison !== 0) {
            return $secondComparison;
        }

        return $this->nanosecond <=> $other->nanosecond;
    }

    private static function isValid(
        int $hour,
        int $minute,
        int $second,
        int $nanosecond,
    ): bool {
        if ($hour < 0 || $hour > 23) {
            return false;
        }

        if ($minute < 0 || $minute > 59) {
            return false;
        }

        if ($second < 0 || $second > 59) {
            return false;
        }

        if ($nanosecond < 0 || $nanosecond >= self::NANOS_PER_SECOND) {
            return false;
        }

        return true;
    }
}
