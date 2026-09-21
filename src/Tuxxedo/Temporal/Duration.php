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

class Duration implements DurationInterface
{
    private const int NANOS_PER_SECOND = 1_000_000_000;
    private const int NANOS_PER_MICROSECOND = 1_000;
    private const int NANOS_PER_MILLISECOND = 1_000_000;
    private const int SECONDS_PER_MINUTE = 60;
    private const int SECONDS_PER_HOUR = 3_600;

    private function __construct(
        public readonly int $seconds,
        public readonly int $nanoseconds,
        public readonly bool $negative,
    ) {
    }

    public static function fromSeconds(
        int $seconds,
        int $nanoseconds = 0,
    ): self {
        if ($seconds < 0) {
            throw TemporalException::fromNegativeMagnitude(
                factory: __METHOD__,
                value: $seconds,
            );
        }

        if ($nanoseconds < 0 || $nanoseconds >= self::NANOS_PER_SECOND) {
            throw TemporalException::fromNanosecondsOutOfRange(
                nanoseconds: $nanoseconds,
            );
        }

        if ($seconds === 0 && $nanoseconds === 0) {
            return new self(
                seconds: 0,
                nanoseconds: 0,
                negative: false,
            );
        }

        return new self(
            seconds: $seconds,
            nanoseconds: $nanoseconds,
            negative: false,
        );
    }

    public static function fromNanoseconds(
        int $nanoseconds,
    ): self {
        if ($nanoseconds < 0) {
            throw TemporalException::fromNegativeMagnitude(
                factory: __METHOD__,
                value: $nanoseconds,
            );
        }

        return new self(
            seconds: \intdiv($nanoseconds, self::NANOS_PER_SECOND),
            nanoseconds: $nanoseconds % self::NANOS_PER_SECOND,
            negative: false,
        );
    }

    public static function fromMicroseconds(
        int $microseconds,
    ): self {
        if ($microseconds < 0) {
            throw TemporalException::fromNegativeMagnitude(
                factory: __METHOD__,
                value: $microseconds,
            );
        }

        self::assertMultiplyDoesNotOverflow(
            a: $microseconds,
            b: self::NANOS_PER_MICROSECOND,
        );

        return self::fromNanoseconds(
            nanoseconds: $microseconds * self::NANOS_PER_MICROSECOND,
        );
    }

    public static function fromMilliseconds(
        int $milliseconds,
    ): self {
        if ($milliseconds < 0) {
            throw TemporalException::fromNegativeMagnitude(
                factory: __METHOD__,
                value: $milliseconds,
            );
        }

        self::assertMultiplyDoesNotOverflow(
            a: $milliseconds,
            b: self::NANOS_PER_MILLISECOND,
        );

        return self::fromNanoseconds(
            nanoseconds: $milliseconds * self::NANOS_PER_MILLISECOND,
        );
    }

    public static function fromMinutes(
        int $minutes,
    ): self {
        if ($minutes < 0) {
            throw TemporalException::fromNegativeMagnitude(
                factory: __METHOD__,
                value: $minutes,
            );
        }

        self::assertMultiplyDoesNotOverflow(
            a: $minutes,
            b: self::SECONDS_PER_MINUTE,
        );

        return self::fromSeconds(
            seconds: $minutes * self::SECONDS_PER_MINUTE,
        );
    }

    public static function fromHours(
        int $hours,
    ): self {
        if ($hours < 0) {
            throw TemporalException::fromNegativeMagnitude(
                factory: __METHOD__,
                value: $hours,
            );
        }

        self::assertMultiplyDoesNotOverflow(
            a: $hours,
            b: self::SECONDS_PER_HOUR,
        );

        return self::fromSeconds(
            seconds: $hours * self::SECONDS_PER_HOUR,
        );
    }

    public static function fromIso8601DurationString(
        string $specification,
    ): self {
        $matches = [];
        $matched = \preg_match(
            pattern: '/^(-)?P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)(?:\.(\d+))?S)?)?$/',
            subject: $specification,
            matches: $matches,
        );

        if ($matched !== 1) {
            throw TemporalException::fromMalformedIso8601Duration(
                input: $specification,
            );
        }

        $signPart = $matches[1] ?? '';
        $daysPart = $matches[2] ?? '';
        $hoursPart = $matches[3] ?? '';
        $minutesPart = $matches[4] ?? '';
        $secondsPart = $matches[5] ?? '';
        $fractionPart = $matches[6] ?? '';

        if ($daysPart !== '') {
            throw TemporalException::fromIso8601DurationWithDateComponents(
                input: $specification,
            );
        }

        if ($hoursPart === '' && $minutesPart === '' && $secondsPart === '') {
            throw TemporalException::fromMalformedIso8601Duration(
                input: $specification,
            );
        }

        $hours = $hoursPart === ''
            ? 0
            : (int) $hoursPart;
        $minutes = $minutesPart === ''
            ? 0
            : (int) $minutesPart;
        $seconds = $secondsPart === ''
            ? 0
            : (int) $secondsPart;

        $totalSeconds = $hours * self::SECONDS_PER_HOUR + $minutes * self::SECONDS_PER_MINUTE + $seconds;

        $nanoseconds = 0;

        if ($fractionPart !== '') {
            $padded = \str_pad(
                string: \substr($fractionPart, 0, 9),
                length: 9,
                pad_string: '0',
                pad_type: \STR_PAD_RIGHT,
            );
            $nanoseconds = (int) $padded;
        }

        $duration = self::fromSeconds(
            seconds: $totalSeconds,
            nanoseconds: $nanoseconds,
        );

        return $signPart === '-'
            ? $duration->negate()
            : $duration;
    }

    public function add(
        DurationInterface $other,
    ): self {
        $signedNanos = self::toSignedNanosecondsOf($this) +
            self::toSignedNanosecondsOf($other);

        return self::fromSignedNanoseconds(
            signedNanoseconds: $signedNanos,
        );
    }

    public function sub(
        DurationInterface $other,
    ): self {
        return $this->add(
            other: $other->negate(),
        );
    }

    public function multiplyBy(
        int $factor,
    ): self {
        if ($factor === 0) {
            return new self(
                seconds: 0,
                nanoseconds: 0,
                negative: false,
            );
        }

        $factorAbs = \abs($factor);
        $flipSign = $factor < 0;

        self::assertMultiplyDoesNotOverflow(
            a: $this->nanoseconds,
            b: $factorAbs,
        );
        $totalNanos = $this->nanoseconds * $factorAbs;
        $carry = \intdiv($totalNanos, self::NANOS_PER_SECOND);
        $newNanos = $totalNanos % self::NANOS_PER_SECOND;

        self::assertMultiplyDoesNotOverflow(
            a: $this->seconds,
            b: $factorAbs,
        );

        $secondsProduct = $this->seconds * $factorAbs;

        self::assertAddDoesNotOverflow(
            a: $secondsProduct,
            b: $carry,
        );

        $newSeconds = $secondsProduct + $carry;
        $negative = $this->negative !== $flipSign;

        if ($newSeconds === 0 && $newNanos === 0) {
            return new self(
                seconds: 0,
                nanoseconds: 0,
                negative: false,
            );
        }

        return new self(
            seconds: $newSeconds,
            nanoseconds: $newNanos,
            negative: $negative,
        );
    }

    public function divideBy(
        int $divisor,
    ): self {
        if ($divisor === 0) {
            throw TemporalException::fromDivisionByZero();
        }

        $divisorAbs = \abs($divisor);
        $flipSign = $divisor < 0;

        $wholeSeconds = \intdiv($this->seconds, $divisorAbs);
        $remainderSeconds = $this->seconds % $divisorAbs;

        self::assertMultiplyDoesNotOverflow(
            a: $remainderSeconds,
            b: self::NANOS_PER_SECOND,
        );

        $extraNanos = $remainderSeconds * self::NANOS_PER_SECOND + $this->nanoseconds;
        $wholeNanos = \intdiv($extraNanos, $divisorAbs);

        self::assertAddDoesNotOverflow(
            a: $wholeSeconds,
            b: \intdiv($wholeNanos, self::NANOS_PER_SECOND),
        );

        $newSeconds = $wholeSeconds + \intdiv($wholeNanos, self::NANOS_PER_SECOND);
        $newNanos = $wholeNanos % self::NANOS_PER_SECOND;
        $negative = $this->negative !== $flipSign;

        if ($newSeconds === 0 && $newNanos === 0) {
            return new self(
                seconds: 0,
                nanoseconds: 0,
                negative: false,
            );
        }

        return new self(
            seconds: $newSeconds,
            nanoseconds: $newNanos,
            negative: $negative,
        );
    }

    public function negate(): self
    {
        if ($this->seconds === 0 && $this->nanoseconds === 0) {
            return $this;
        }

        return new self(
            seconds: $this->seconds,
            nanoseconds: $this->nanoseconds,
            negative: !$this->negative,
        );
    }

    public function absolute(): self
    {
        if (!$this->negative) {
            return $this;
        }

        return new self(
            seconds: $this->seconds,
            nanoseconds: $this->nanoseconds,
            negative: false,
        );
    }

    public function equals(
        DurationInterface $other,
    ): bool {
        return $this->seconds === $other->seconds &&
            $this->nanoseconds === $other->nanoseconds &&
            $this->negative === $other->negative;
    }

    public function isZero(): bool
    {
        return $this->seconds === 0 && $this->nanoseconds === 0;
    }

    public function isPositive(): bool
    {
        return !$this->negative && !$this->isZero();
    }

    public function isNegative(): bool
    {
        return $this->negative;
    }

    public static function compare(
        DurationInterface $a,
        DurationInterface $b,
    ): int {

        $aSigned = self::toSignedNanosecondsOf(
            duration: $a,
        );

        $bSigned = self::toSignedNanosecondsOf(
            duration: $b,
        );

        return $aSigned <=> $bSigned;
    }

    private static function toSignedNanosecondsOf(
        DurationInterface $duration,
    ): int {
        self::assertMultiplyDoesNotOverflow(
            a: $duration->seconds,
            b: self::NANOS_PER_SECOND,
        );

        $magnitude = $duration->seconds * self::NANOS_PER_SECOND + $duration->nanoseconds;

        return $duration->negative
            ? -$magnitude
            : $magnitude;
    }

    private static function fromSignedNanoseconds(
        int $signedNanoseconds,
    ): self {
        if ($signedNanoseconds === 0) {
            return new self(
                seconds: 0,
                nanoseconds: 0,
                negative: false,
            );
        }

        $negative = $signedNanoseconds < 0;
        $magnitude = \abs($signedNanoseconds);

        return new self(
            seconds: \intdiv($magnitude, self::NANOS_PER_SECOND),
            nanoseconds: $magnitude % self::NANOS_PER_SECOND,
            negative: $negative,
        );
    }

    private static function assertMultiplyDoesNotOverflow(
        int $a,
        int $b,
    ): void {
        if ($a === 0 || $b === 0) {
            return;
        }

        $aAbs = \abs($a);
        $bAbs = \abs($b);

        if ($aAbs > \intdiv(\PHP_INT_MAX, $bAbs)) {
            throw TemporalException::fromDurationOverflow();
        }
    }

    private static function assertAddDoesNotOverflow(
        int $a,
        int $b,
    ): void {
        if ($b > 0 && $a > \PHP_INT_MAX - $b) {
            throw TemporalException::fromDurationOverflow();
        }
    }
}
