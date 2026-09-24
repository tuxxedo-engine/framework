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

namespace Tuxxedo\Temporal\Humanizer;

use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\Message\DefaultEnglishMessageFormatter;
use Tuxxedo\Temporal\Humanizer\Message\MessageFormatterInterface;
use Tuxxedo\Temporal\InstantInterface;

class Humanizer
{
    private const int JUST_NOW_CUTOFF_SECONDS = 60;

    private const int SECONDS_PER_MINUTE = 60;

    private const int SECONDS_PER_HOUR = 3_600;

    private const int SECONDS_PER_DAY = 86_400;

    private const int SECONDS_PER_WEEK = 604_800;

    private const float SECONDS_PER_MONTH = 2_629_746.0;

    private const float SECONDS_PER_YEAR = 31_556_952.0;

    private const int RELATIVE_UNITS_IN_DURATION = 2;

    private readonly MessageFormatterInterface $messageFormatter;

    public function __construct(
        private readonly ClockInterface $clock,
        ?MessageFormatterInterface $messageFormatter = null,
    ) {
        $this->messageFormatter = $messageFormatter ?? new DefaultEnglishMessageFormatter();
    }

    public function diffForHumans(
        InstantInterface $moment,
        ?InstantInterface $reference = null,
    ): string {
        $anchor = $reference ?? $this->clock->now();
        $diffSeconds = $moment->toUnixTimestamp() - $anchor->toUnixTimestamp();
        $absSeconds = \abs($diffSeconds);

        if ($absSeconds < self::JUST_NOW_CUTOFF_SECONDS) {
            return $this->messageFormatter->forJustNow();
        }

        $direction = $diffSeconds < 0
            ? RelativeDirection::PAST
            : RelativeDirection::FUTURE;
        [$count, $unit] = self::selectLargestUnit(seconds: $absSeconds);

        return $this->messageFormatter->forRelativeMoment(
            count: $count,
            unit: $unit,
            direction: $direction,
        );
    }

    public function formatDuration(
        DurationInterface $duration,
        HumanizedStyle $style = HumanizedStyle::LONG,
    ): string {
        $totalSeconds = $duration->seconds;

        if ($totalSeconds === 0) {
            return $this->messageFormatter->forZeroDuration(style: $style);
        }

        return $this->messageFormatter->forDuration(
            parts: self::breakdown(seconds: $totalSeconds),
            style: $style,
        );
    }

    /**
     * @return array{int, RelativeUnit}
     */
    private static function selectLargestUnit(
        int $seconds,
    ): array {
        if ($seconds >= self::SECONDS_PER_YEAR) {
            return [
                (int) \floor($seconds / self::SECONDS_PER_YEAR),
                RelativeUnit::YEARS,
            ];
        }

        if ($seconds >= self::SECONDS_PER_MONTH) {
            return [
                (int) \floor($seconds / self::SECONDS_PER_MONTH),
                RelativeUnit::MONTHS,
            ];
        }

        if ($seconds >= self::SECONDS_PER_WEEK) {
            return [
                \intdiv($seconds, self::SECONDS_PER_WEEK),
                RelativeUnit::WEEKS,
            ];
        }

        if ($seconds >= self::SECONDS_PER_DAY) {
            return [
                \intdiv($seconds, self::SECONDS_PER_DAY),
                RelativeUnit::DAYS,
            ];
        }

        if ($seconds >= self::SECONDS_PER_HOUR) {
            return [
                \intdiv($seconds, self::SECONDS_PER_HOUR),
                RelativeUnit::HOURS,
            ];
        }

        if ($seconds >= self::SECONDS_PER_MINUTE) {
            return [
                \intdiv($seconds, self::SECONDS_PER_MINUTE),
                RelativeUnit::MINUTES,
            ];
        }

        // @codeCoverageIgnoreStart
        return [
            $seconds,
            RelativeUnit::SECONDS,
        ];
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return list<array{int, RelativeUnit}>
     */
    private static function breakdown(
        int $seconds,
    ): array {
        $parts = [];
        $remaining = $seconds;

        foreach (self::unitTable() as [$unitSize, $unitEnum]) {
            if ($remaining < $unitSize) {
                continue;
            }

            $count = \intdiv($remaining, $unitSize);
            $remaining -= $count * $unitSize;
            $parts[] = [
                $count,
                $unitEnum,
            ];

            if (\sizeof($parts) >= self::RELATIVE_UNITS_IN_DURATION) {
                return $parts;
            }
        }

        return $parts;
    }

    /**
     * @return list<array{int, RelativeUnit}>
     */
    private static function unitTable(): array
    {
        return [
            [
                (int) self::SECONDS_PER_YEAR,
                RelativeUnit::YEARS,
            ],
            [
                (int) self::SECONDS_PER_MONTH,
                RelativeUnit::MONTHS,
            ],
            [
                self::SECONDS_PER_WEEK,
                RelativeUnit::WEEKS,
            ],
            [
                self::SECONDS_PER_DAY,
                RelativeUnit::DAYS,
            ],
            [
                self::SECONDS_PER_HOUR,
                RelativeUnit::HOURS,
            ],
            [
                self::SECONDS_PER_MINUTE,
                RelativeUnit::MINUTES,
            ],
            [
                1,
                RelativeUnit::SECONDS,
            ],
        ];
    }
}
