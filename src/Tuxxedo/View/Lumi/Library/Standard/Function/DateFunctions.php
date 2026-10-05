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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\Humanizer;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTimeInterface;
use Tuxxedo\Temporal\SystemClock;
use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\Temporal\TimeZoneInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class DateFunctions
{
    private readonly ClockInterface $clock;
    private readonly Humanizer $humanizer;

    public function __construct(
        ?ClockInterface $clock = null,
        ?Humanizer $humanizer = null,
    ) {
        $this->clock = $clock ?? new SystemClock();
        $this->humanizer = $humanizer ?? new Humanizer($this->clock);
    }

    #[LumiFunction('date', aliases: ['time'])]
    public function date(
        string $format,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string|null $value = null,
    ): string {
        return TemporalCoercion::format(
            value: $value ?? $this->clock->now(),
            pattern: $format,
        );
    }

    #[LumiFunction('date_duration')]
    public function dateDuration(
        DurationInterface|int|string $value,
        string $style = 'long',
    ): string {
        return $this->humanizer->formatDuration(
            duration: self::coerceDuration($value),
            style: match (\strtolower($style)) {
                'short' => HumanizedStyle::SHORT,
                'narrow' => HumanizedStyle::NARROW,
                default => HumanizedStyle::LONG,
            },
        );
    }

    #[LumiFunction('date_local')]
    public function dateLocal(
        InstantInterface|\DateTimeInterface|int|string $value,
        TimeZoneInterface|string|null $timeZone = null,
        string $format = 'Y-m-d H:i:s',
    ): string {
        return TemporalCoercion::toInstant($value)
            ->withTimeZone(self::resolveTimeZone($timeZone))
            ->format($format);
    }

    #[LumiFunction('date_now')]
    public function dateNow(
        string $format = 'Y-m-d H:i:s',
    ): string {
        return $this->clock->now()->format($format);
    }

    #[LumiFunction('date_today')]
    public function dateToday(): string
    {
        return $this->clock->now()->format('Y-m-d');
    }

    #[LumiFunction('now')]
    public function now(): string
    {
        return (string) $this->clock->now()->toUnixTimestamp();
    }

    #[LumiFunction('time_now')]
    public function timeNow(
        string $format = 'H:i:s',
    ): string {
        return $this->clock->now()->format($format);
    }

    private static function coerceDuration(
        DurationInterface|int|string $value,
    ): DurationInterface {
        if ($value instanceof DurationInterface) {
            return $value;
        }

        if (\is_int($value)) {
            return Duration::fromSeconds($value);
        }

        return Duration::fromIso8601DurationString($value);
    }

    private static function resolveTimeZone(
        TimeZoneInterface|string|null $argument,
    ): TimeZoneInterface {
        if ($argument instanceof TimeZoneInterface) {
            return $argument;
        }

        if (\is_string($argument)) {
            return TimeZone::parse($argument);
        }

        return TimeZone::parse(\date_default_timezone_get());
    }
}
