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

namespace Tuxxedo\Temporal\Humanizer\Message;

use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\RelativeDirection;
use Tuxxedo\Temporal\Humanizer\RelativeUnit;

class DefaultEnglishMessageFormatter implements MessageFormatterInterface
{
    public function forJustNow(): string
    {
        return 'just now';
    }

    public function forRelativeMoment(
        int $count,
        RelativeUnit $unit,
        RelativeDirection $direction,
    ): string {
        $word = $this->longUnitWord(unit: $unit, count: $count);

        return $direction === RelativeDirection::PAST
            ? \sprintf('%d %s ago', $count, $word)
            : \sprintf('in %d %s', $count, $word);
    }

    public function forDuration(
        array $parts,
        HumanizedStyle $style,
    ): string {
        return match ($style) { // @codeCoverageIgnore
            HumanizedStyle::LONG => $this->joinLong(parts: $parts),
            HumanizedStyle::SHORT => $this->joinShort(parts: $parts, separator: ' '),
            HumanizedStyle::NARROW => $this->joinShort(parts: $parts, separator: ''),
        };
    }

    public function forZeroDuration(
        HumanizedStyle $style,
    ): string {
        return match ($style) { // @codeCoverageIgnore
            HumanizedStyle::LONG => '0 seconds',
            HumanizedStyle::SHORT, HumanizedStyle::NARROW => '0s',
        };
    }

    /**
     * @param list<array{int, RelativeUnit}> $parts
     */
    private function joinLong(
        array $parts,
    ): string {
        $rendered = [];

        foreach ($parts as [$count, $unit]) {
            $rendered[] = \sprintf('%d %s', $count, $this->longUnitWord(unit: $unit, count: $count));
        }

        return \join(', ', $rendered);
    }

    /**
     * @param list<array{int, RelativeUnit}> $parts
     */
    private function joinShort(
        array $parts,
        string $separator,
    ): string {
        $rendered = [];

        foreach ($parts as [$count, $unit]) {
            $rendered[] = \sprintf('%d%s', $count, $this->shortUnitLetter(unit: $unit));
        }

        return \join($separator, $rendered);
    }

    private function longUnitWord(
        RelativeUnit $unit,
        int $count,
    ): string {
        $singular = match ($unit) {
            RelativeUnit::SECONDS => 'second',
            RelativeUnit::MINUTES => 'minute',
            RelativeUnit::HOURS => 'hour',
            RelativeUnit::DAYS => 'day',
            RelativeUnit::WEEKS => 'week',
            RelativeUnit::MONTHS => 'month',
            RelativeUnit::YEARS => 'year',
        };

        return $count === 1
            ? $singular
            : $singular . 's';
    }

    private function shortUnitLetter(
        RelativeUnit $unit,
    ): string {
        return match ($unit) { // @codeCoverageIgnore
            RelativeUnit::SECONDS => 's',
            RelativeUnit::MINUTES => 'm',
            RelativeUnit::HOURS => 'h',
            RelativeUnit::DAYS => 'd',
            RelativeUnit::WEEKS => 'w',
            RelativeUnit::MONTHS => 'mo',
            RelativeUnit::YEARS => 'y',
        };
    }
}
