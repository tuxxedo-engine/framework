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

class Instant implements InstantInterface
{
    private const int NANOS_PER_SECOND = 1_000_000_000;
    private const int NANOS_PER_MICROSECOND = 1_000;

    private function __construct(
        public readonly \DateTimeImmutable $dateTime,
        public readonly int $nanosecondFraction,
    ) {
    }

    public static function now(
        ?ClockInterface $clock = null,
    ): InstantInterface {
        return ($clock ?? new SystemClock())->now();
    }

    public static function parse(
        string $input,
        ?\DateTimeZone $default = null,
    ): self {
        try {
            $dateTime = new \DateTimeImmutable(
                datetime: $input,
                timezone: $default,
            );
        } catch (\Exception $exception) {
            throw TemporalException::fromMalformedInstantParse(
                input: $input,
            );
        }

        return new self(
            dateTime: $dateTime,
            nanosecondFraction: 0,
        );
    }

    public static function fromUnixTimestamp(
        int $timestamp,
        ?\DateTimeZone $timeZone = null,
    ): self {
        $dateTime = (new \DateTimeImmutable(
            datetime: '@' . $timestamp,
        ));

        if ($timeZone !== null) {
            $dateTime = $dateTime->setTimezone(
                timezone: $timeZone,
            );
        }

        return new self(
            dateTime: $dateTime,
            nanosecondFraction: 0,
        );
    }

    public static function fromDateTime(
        \DateTimeImmutable $dateTime,
    ): self {
        return new self(
            dateTime: $dateTime,
            nanosecondFraction: 0,
        );
    }

    public function plus(
        DurationInterface $duration,
    ): self {
        return $this->shift(
            durationSignedNanoseconds: self::signedNanosecondsOf(
                duration: $duration,
            ),
        );
    }

    public function minus(
        DurationInterface $duration,
    ): self {
        return $this->shift(
            durationSignedNanoseconds: -self::signedNanosecondsOf(
                duration: $duration,
            ),
        );
    }

    public function difference(
        InstantInterface $other,
    ): DurationInterface {
        $thisTotal = self::totalNanosecondsFromEpochOf(
            instant: $this,
        );
        $otherTotal = self::totalNanosecondsFromEpochOf(
            instant: $other,
        );

        $delta = $thisTotal - $otherTotal;

        if ($delta === 0) {
            return Duration::fromSeconds(
                seconds: 0,
            );
        }

        $negative = $delta < 0;
        $magnitude = \abs($delta);

        $seconds = \intdiv($magnitude, self::NANOS_PER_SECOND);
        $nanoseconds = $magnitude % self::NANOS_PER_SECOND;

        $duration = Duration::fromSeconds(
            seconds: $seconds,
            nanoseconds: $nanoseconds,
        );

        return $negative
            ? $duration->negate()
            : $duration;
    }

    public function isBefore(
        InstantInterface $other,
    ): bool {
        return self::totalNanosecondsFromEpochOf($this) < self::totalNanosecondsFromEpochOf($other);
    }

    public function isAfter(
        InstantInterface $other,
    ): bool {
        return self::totalNanosecondsFromEpochOf($this) > self::totalNanosecondsFromEpochOf($other);
    }

    public function equals(
        InstantInterface $other,
    ): bool {
        return self::totalNanosecondsFromEpochOf($this) === self::totalNanosecondsFromEpochOf($other);
    }

    public function format(
        string $pattern,
    ): string {
        return $this->dateTime->format(
            format: $pattern,
        );
    }

    public function toIso8601(): string
    {
        return $this->dateTime->format(
            format: \DateTimeInterface::ATOM,
        );
    }

    public function toRfc3339(): string
    {
        return $this->dateTime->format(
            format: \DateTimeInterface::RFC3339,
        );
    }

    public function toAtom(): string
    {
        return $this->dateTime->format(
            format: \DateTimeInterface::ATOM,
        );
    }

    public function toUnixTimestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    public function toDateTime(): \DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function dayOfWeek(): DayOfWeek
    {
        return DayOfWeek::fromInstant($this);
    }

    public function month(): Month
    {
        return Month::fromInstant($this);
    }

    private function shift(
        int $durationSignedNanoseconds,
    ): self {
        $currentTotal = self::totalNanosecondsFromEpochOf(
            instant: $this,
        );

        $resultTotal = $currentTotal + $durationSignedNanoseconds;

        $newSeconds = \intdiv($resultTotal, self::NANOS_PER_SECOND);
        $newNanosOfSecond = $resultTotal % self::NANOS_PER_SECOND;

        if ($newNanosOfSecond < 0) {
            $newSeconds -= 1;
            $newNanosOfSecond += self::NANOS_PER_SECOND;
        }

        $newMicros = \intdiv($newNanosOfSecond, self::NANOS_PER_MICROSECOND);
        $newNanoFraction = $newNanosOfSecond % self::NANOS_PER_MICROSECOND;

        $rebuilt = \DateTimeImmutable::createFromFormat(
            format: 'U.u',
            datetime: \sprintf(
                '%d.%06d',
                $newSeconds,
                $newMicros,
            ),
        );

        if ($rebuilt === false) {
            throw TemporalException::fromDurationOverflow(); // @codeCoverageIgnore
        }

        $rebuilt = $rebuilt->setTimezone(
            timezone: $this->dateTime->getTimezone(),
        );

        return new self(
            dateTime: $rebuilt,
            nanosecondFraction: $newNanoFraction,
        );
    }

    private static function signedNanosecondsOf(
        DurationInterface $duration,
    ): int {
        $magnitude = $duration->seconds * self::NANOS_PER_SECOND + $duration->nanoseconds;

        return $duration->negative
            ? -$magnitude
            : $magnitude;
    }

    private static function totalNanosecondsFromEpochOf(
        InstantInterface $instant,
    ): int {
        $epochSeconds = $instant->dateTime->getTimestamp();
        $microseconds = (int) $instant->dateTime->format(
            format: 'u',
        );

        return $epochSeconds * self::NANOS_PER_SECOND + $microseconds * self::NANOS_PER_MICROSECOND + $instant->nanosecondFraction;
    }
}
