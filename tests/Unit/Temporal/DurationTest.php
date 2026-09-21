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

namespace Unit\Temporal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\TemporalException;

class DurationTest extends TestCase
{
    public function testFromSecondsHoldsBothComponents(): void
    {
        $duration = Duration::fromSeconds(
            seconds: 42,
            nanoseconds: 500,
        );

        self::assertSame(42, $duration->seconds);
        self::assertSame(500, $duration->nanoseconds);
        self::assertFalse($duration->negative);
    }

    public function testFromSecondsZeroIsPositive(): void
    {
        $duration = Duration::fromSeconds(
            seconds: 0,
        );

        self::assertTrue($duration->isZero());
        self::assertFalse($duration->isNegative());
    }

    public function testFromSecondsRejectsNegative(): void
    {
        $this->expectException(TemporalException::class);

        Duration::fromSeconds(
            seconds: -1,
        );
    }

    public function testFromSecondsRejectsOutOfRangeNanoseconds(): void
    {
        $this->expectException(TemporalException::class);

        Duration::fromSeconds(
            seconds: 0,
            nanoseconds: 1_000_000_000,
        );
    }

    public function testFromSecondsRejectsNegativeNanoseconds(): void
    {
        $this->expectException(TemporalException::class);

        Duration::fromSeconds(
            seconds: 0,
            nanoseconds: -1,
        );
    }

    public function testFromNanosecondsSplitsCorrectly(): void
    {
        $duration = Duration::fromNanoseconds(
            nanoseconds: 2_500_000_000,
        );

        self::assertSame(2, $duration->seconds);
        self::assertSame(500_000_000, $duration->nanoseconds);
    }

    public function testFromMicrosecondsConvertsToNanos(): void
    {
        $duration = Duration::fromMicroseconds(
            microseconds: 1_500_000,
        );

        self::assertSame(1, $duration->seconds);
        self::assertSame(500_000_000, $duration->nanoseconds);
    }

    public function testFromMillisecondsConvertsToSeconds(): void
    {
        $duration = Duration::fromMilliseconds(
            milliseconds: 1_250,
        );

        self::assertSame(1, $duration->seconds);
        self::assertSame(250_000_000, $duration->nanoseconds);
    }

    public function testFromMinutesConvertsToSeconds(): void
    {
        $duration = Duration::fromMinutes(
            minutes: 3,
        );

        self::assertSame(180, $duration->seconds);
        self::assertSame(0, $duration->nanoseconds);
    }

    public function testFromHoursConvertsToSeconds(): void
    {
        $duration = Duration::fromHours(
            hours: 2,
        );

        self::assertSame(7_200, $duration->seconds);
    }

    /**
     * @return iterable<string, array{callable(): DurationInterface}>
     */
    public static function negativeMagnitudeFactories(): iterable
    {
        yield 'fromNanoseconds' => [
            static fn (): DurationInterface => Duration::fromNanoseconds(nanoseconds: -1),
        ];

        yield 'fromMicroseconds' => [
            static fn (): DurationInterface => Duration::fromMicroseconds(microseconds: -1),
        ];

        yield 'fromMilliseconds' => [
            static fn (): DurationInterface => Duration::fromMilliseconds(milliseconds: -1),
        ];

        yield 'fromMinutes' => [
            static fn (): DurationInterface => Duration::fromMinutes(minutes: -1),
        ];

        yield 'fromHours' => [
            static fn (): DurationInterface => Duration::fromHours(hours: -1),
        ];
    }

    /**
     * @param callable(): DurationInterface $factory
     */
    #[DataProvider('negativeMagnitudeFactories')]
    public function testMagnitudeFactoriesRejectNegative(
        callable $factory,
    ): void {
        $caught = null;

        try {
            $factory();
        } catch (TemporalException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(TemporalException::class, $caught);
        self::assertStringContainsString('non-negative', $caught->getMessage());
    }

    public function testFromIso8601BasicTimeComponents(): void
    {
        $duration = Duration::fromIso8601DurationString(
            specification: 'PT1H30M15S',
        );

        self::assertSame(5_415, $duration->seconds);
        self::assertFalse($duration->negative);
    }

    public function testFromIso8601FractionalSeconds(): void
    {
        $duration = Duration::fromIso8601DurationString(
            specification: 'PT0.5S',
        );

        self::assertSame(0, $duration->seconds);
        self::assertSame(500_000_000, $duration->nanoseconds);
    }

    public function testFromIso8601NegativePrefix(): void
    {
        $duration = Duration::fromIso8601DurationString(
            specification: '-PT1H',
        );

        self::assertSame(3_600, $duration->seconds);
        self::assertTrue($duration->negative);
    }

    public function testFromIso8601RejectsDateComponents(): void
    {
        $this->expectException(TemporalException::class);

        Duration::fromIso8601DurationString(
            specification: 'P1D',
        );
    }

    public function testFromIso8601RejectsMalformedInput(): void
    {
        $this->expectException(TemporalException::class);

        Duration::fromIso8601DurationString(
            specification: 'nonsense',
        );
    }

    public function testFromIso8601RejectsEmptyTimeComponent(): void
    {
        $this->expectException(TemporalException::class);

        Duration::fromIso8601DurationString(
            specification: 'PT',
        );
    }

    public function testAddCarriesNanosecondsIntoSeconds(): void
    {
        $result = Duration::fromSeconds(seconds: 0, nanoseconds: 800_000_000)
            ->add(other: Duration::fromSeconds(seconds: 0, nanoseconds: 500_000_000));

        self::assertSame(1, $result->seconds);
        self::assertSame(300_000_000, $result->nanoseconds);
    }

    public function testAddOppositeSignsResolveToDifference(): void
    {
        $positive = Duration::fromSeconds(seconds: 3);
        $negative = Duration::fromSeconds(seconds: 5)->negate();

        $result = $positive->add(other: $negative);

        self::assertTrue($result->isNegative());
        self::assertSame(2, $result->seconds);
    }

    public function testSubIsAddNegated(): void
    {
        $result = Duration::fromSeconds(seconds: 10)
            ->sub(other: Duration::fromSeconds(seconds: 3));

        self::assertSame(7, $result->seconds);
        self::assertFalse($result->negative);
    }

    public function testMultiplyByPositiveFactor(): void
    {
        $result = Duration::fromSeconds(seconds: 2, nanoseconds: 500_000_000)
            ->multiplyBy(factor: 3);

        self::assertSame(7, $result->seconds);
        self::assertSame(500_000_000, $result->nanoseconds);
    }

    public function testMultiplyByNegativeFactorFlipsSign(): void
    {
        $result = Duration::fromSeconds(seconds: 2)->multiplyBy(factor: -3);

        self::assertTrue($result->isNegative());
        self::assertSame(6, $result->seconds);
    }

    public function testMultiplyByZeroYieldsZero(): void
    {
        $result = Duration::fromSeconds(seconds: 42)->multiplyBy(factor: 0);

        self::assertTrue($result->isZero());
    }

    public function testDivideByFractionallyDistributes(): void
    {
        $result = Duration::fromSeconds(seconds: 3)->divideBy(divisor: 2);

        self::assertSame(1, $result->seconds);
        self::assertSame(500_000_000, $result->nanoseconds);
    }

    public function testDivideByRejectsZero(): void
    {
        $this->expectException(TemporalException::class);

        Duration::fromSeconds(seconds: 10)->divideBy(divisor: 0);
    }

    public function testNegateFlipsSign(): void
    {
        $result = Duration::fromSeconds(seconds: 5)->negate();

        self::assertTrue($result->isNegative());
        self::assertSame(5, $result->seconds);
    }

    public function testNegateOnZeroStaysZero(): void
    {
        $result = Duration::fromSeconds(seconds: 0)->negate();

        self::assertFalse($result->negative);
        self::assertTrue($result->isZero());
    }

    public function testAbsoluteReturnsPositive(): void
    {
        $result = Duration::fromSeconds(seconds: 5)->negate()->absolute();

        self::assertFalse($result->negative);
        self::assertSame(5, $result->seconds);
    }

    public function testAbsoluteOnPositiveIsIdentity(): void
    {
        $duration = Duration::fromSeconds(seconds: 3);

        self::assertSame($duration, $duration->absolute());
    }

    public function testEqualsMatchesAllFields(): void
    {
        $a = Duration::fromSeconds(seconds: 5, nanoseconds: 100);
        $b = Duration::fromSeconds(seconds: 5, nanoseconds: 100);

        self::assertTrue($a->equals(other: $b));
    }

    public function testEqualsRejectsSignMismatch(): void
    {
        $a = Duration::fromSeconds(seconds: 5);
        $b = Duration::fromSeconds(seconds: 5)->negate();

        self::assertFalse($a->equals(other: $b));
    }

    public function testCompareOrdersCorrectly(): void
    {
        $a = Duration::fromSeconds(seconds: 3);
        $b = Duration::fromSeconds(seconds: 5);

        self::assertSame(-1, Duration::compare(a: $a, b: $b));
        self::assertSame(1, Duration::compare(a: $b, b: $a));
        self::assertSame(0, Duration::compare(a: $a, b: $a));
    }

    public function testIsPositiveIsFalseForZero(): void
    {
        self::assertFalse(Duration::fromSeconds(seconds: 0)->isPositive());
    }

    public function testIsPositiveIsTrueForPositive(): void
    {
        self::assertTrue(Duration::fromSeconds(seconds: 1)->isPositive());
    }

    public function testIsNegativeIsFalseForZero(): void
    {
        self::assertFalse(Duration::fromSeconds(seconds: 0)->isNegative());
    }

    public function testMultiplyByOnZeroDurationYieldsZero(): void
    {
        $result = Duration::fromSeconds(seconds: 0)->multiplyBy(factor: 5);

        self::assertTrue($result->isZero());
        self::assertFalse($result->isNegative());
    }

    public function testDivideByOnZeroDurationYieldsZero(): void
    {
        $result = Duration::fromSeconds(seconds: 0)->divideBy(divisor: 3);

        self::assertTrue($result->isZero());
        self::assertFalse($result->isNegative());
    }

    public function testAddSelfAndNegatedSelfYieldsZero(): void
    {
        $duration = Duration::fromSeconds(seconds: 7, nanoseconds: 123);

        $result = $duration->add(other: $duration->negate());

        self::assertTrue($result->isZero());
        self::assertFalse($result->isNegative());
    }

    public function testFromMinutesOverflowThrows(): void
    {
        $caught = null;

        try {
            Duration::fromMinutes(minutes: \PHP_INT_MAX);
        } catch (TemporalException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(TemporalException::class, $caught);
        self::assertStringContainsString('overflowed', $caught->getMessage());
    }

    public function testMultiplyByOverflowsInSecondsCarry(): void
    {
        $duration = Duration::fromSeconds(
            seconds: \intdiv(\PHP_INT_MAX, 3),
            nanoseconds: 999_999_999,
        );

        $caught = null;

        try {
            $duration->multiplyBy(factor: 3);
        } catch (TemporalException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(TemporalException::class, $caught);
        self::assertStringContainsString('overflowed', $caught->getMessage());
    }
}
