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

namespace Unit\Temporal\Humanizer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\Humanizer;
use Tuxxedo\Temporal\Instant;

class HumanizerTest extends TestCase
{
    /**
     * @return iterable<string, array{int, string}>
     */
    public static function diffMatrix(): iterable
    {
        yield 'exact now' => [
            0,
            'just now',
        ];

        yield 'below cutoff past' => [
            -30,
            'just now',
        ];

        yield 'below cutoff future' => [
            30,
            'just now',
        ];

        yield 'one minute past' => [
            -60,
            '1 minute ago',
        ];

        yield 'five minutes past' => [
            -300,
            '5 minutes ago',
        ];

        yield 'one minute future' => [
            60,
            'in 1 minute',
        ];

        yield 'one hour past' => [
            -3_600,
            '1 hour ago',
        ];

        yield 'two hours future' => [
            7_200,
            'in 2 hours',
        ];

        yield 'one day past' => [
            -86_400,
            '1 day ago',
        ];

        yield 'three days future' => [
            259_200,
            'in 3 days',
        ];

        yield 'one week past' => [
            -604_800,
            '1 week ago',
        ];

        yield 'one month past' => [
            -2_629_746,
            '1 month ago',
        ];

        yield 'two years past' => [
            -63_113_904,
            '2 years ago',
        ];
    }

    #[DataProvider('diffMatrix')]
    public function testDiffForHumans(
        int $offsetSeconds,
        string $expected,
    ): void {
        $reference = Instant::parse(input: '2026-07-16T12:00:00Z');
        $clock = new FixedClock(instant: $reference);

        $moment = $offsetSeconds >= 0
            ? $reference->plus(duration: Duration::fromSeconds(seconds: $offsetSeconds))
            : $reference->minus(duration: Duration::fromSeconds(seconds: \abs($offsetSeconds)));

        $humanizer = new Humanizer(clock: $clock);

        self::assertSame($expected, $humanizer->diffForHumans(moment: $moment));
    }

    public function testDiffForHumansUsesExplicitReference(): void
    {
        $reference = Instant::parse(input: '2026-01-01T00:00:00Z');
        $moment = Instant::parse(input: '2026-01-01T00:30:00Z');
        $humanizer = new Humanizer(
            clock: new FixedClock(instant: Instant::parse(input: '2030-01-01T00:00:00Z')),
        );

        self::assertSame(
            'in 30 minutes',
            $humanizer->diffForHumans(moment: $moment, reference: $reference),
        );
    }

    public function testFormatDurationLongTwoUnits(): void
    {
        $humanizer = $this->humanizer();

        self::assertSame(
            '3 days, 4 hours',
            $humanizer->formatDuration(
                duration: Duration::fromSeconds(seconds: 3 * 86_400 + 4 * 3_600),
            ),
        );
    }

    public function testFormatDurationShortStyle(): void
    {
        $humanizer = $this->humanizer();

        self::assertSame(
            '3d 4h',
            $humanizer->formatDuration(
                duration: Duration::fromSeconds(seconds: 3 * 86_400 + 4 * 3_600),
                style: HumanizedStyle::SHORT,
            ),
        );
    }

    public function testFormatDurationNarrowStyle(): void
    {
        $humanizer = $this->humanizer();

        self::assertSame(
            '3d4h',
            $humanizer->formatDuration(
                duration: Duration::fromSeconds(seconds: 3 * 86_400 + 4 * 3_600),
                style: HumanizedStyle::NARROW,
            ),
        );
    }

    public function testFormatDurationSingleUnitLong(): void
    {
        $humanizer = $this->humanizer();

        self::assertSame(
            '1 minute',
            $humanizer->formatDuration(duration: Duration::fromMinutes(minutes: 1)),
        );
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function shortUnitLetterMatrix(): iterable
    {
        yield 'seconds' => [
            30,
            '30s',
        ];

        yield 'minutes' => [
            300,
            '5m',
        ];

        yield 'hours' => [
            2 * 3_600,
            '2h',
        ];

        yield 'days' => [
            3 * 86_400,
            '3d',
        ];

        yield 'weeks' => [
            604_800,
            '1w',
        ];

        yield 'months' => [
            2_629_746,
            '1mo',
        ];

        yield 'years' => [
            31_556_952,
            '1y',
        ];
    }

    #[DataProvider('shortUnitLetterMatrix')]
    public function testFormatDurationShortEmitsExpectedUnitLetter(
        int $seconds,
        string $expected,
    ): void {
        $humanizer = $this->humanizer();

        self::assertSame(
            $expected,
            $humanizer->formatDuration(
                duration: Duration::fromSeconds(seconds: $seconds),
                style: HumanizedStyle::SHORT,
            ),
        );
    }

    public function testFormatDurationZeroLong(): void
    {
        $humanizer = $this->humanizer();

        self::assertSame(
            '0 seconds',
            $humanizer->formatDuration(duration: Duration::fromSeconds(seconds: 0)),
        );
    }

    public function testFormatDurationZeroShort(): void
    {
        $humanizer = $this->humanizer();

        self::assertSame(
            '0s',
            $humanizer->formatDuration(
                duration: Duration::fromSeconds(seconds: 0),
                style: HumanizedStyle::SHORT,
            ),
        );
    }

    public function testFormatDurationZeroNarrow(): void
    {
        $humanizer = $this->humanizer();

        self::assertSame(
            '0s',
            $humanizer->formatDuration(
                duration: Duration::fromSeconds(seconds: 0),
                style: HumanizedStyle::NARROW,
            ),
        );
    }

    public function testFormatDurationCapsAtTwoLargestUnits(): void
    {
        $humanizer = $this->humanizer();

        self::assertSame(
            '1 year, 2 months',
            $humanizer->formatDuration(
                duration: Duration::fromSeconds(
                    seconds: 31_556_952 + 2 * 2_629_746 + 3 * 86_400 + 4 * 3_600 + 5 * 60 + 6,
                ),
            ),
        );
    }

    public function testInstantDiffForHumansProxy(): void
    {
        $reference = Instant::parse(input: '2026-07-16T12:00:00Z');
        $moment = $reference->minus(duration: Duration::fromHours(hours: 3));
        $humanizer = new Humanizer(clock: new FixedClock(instant: $reference));

        self::assertSame(
            '3 hours ago',
            $moment->diffForHumans(humanizer: $humanizer),
        );
    }

    public function testInstantDiffForHumansProxyWithExplicitReference(): void
    {
        $moment = Instant::parse(input: '2026-01-01T00:00:00Z');
        $reference = Instant::parse(input: '2026-01-01T00:30:00Z');
        $humanizer = new Humanizer(
            clock: new FixedClock(instant: Instant::parse(input: '2030-01-01T00:00:00Z')),
        );

        self::assertSame(
            '30 minutes ago',
            $moment->diffForHumans(humanizer: $humanizer, reference: $reference),
        );
    }

    private function humanizer(): Humanizer
    {
        return new Humanizer(
            clock: new FixedClock(instant: Instant::parse(input: '2026-07-16T12:00:00Z')),
        );
    }
}
