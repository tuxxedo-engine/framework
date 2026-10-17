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
use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\Temporal\TemporalException;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\Temporal\TimeZoneInterface;
use Tuxxedo\View\Lumi\Library\Attribute\Context;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class DateFunctions
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly Humanizer $humanizer,
    ) {
    }

    #[LumiFunction('date', aliases: ['time'])]
    public function date(
        #[Context]
        RuntimeContextInterface $context,
        string $format,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string|null $value = null,
    ): string {
        return TemporalCoercion::format(
            value: $value ?? $this->clock->now(),
            pattern: $format,
            timeZone: self::resolveDirectiveTimeZone($context),
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
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|\DateTimeInterface|int|string $value,
        TimeZoneInterface|string|null $timeZone = null,
        string $format = 'Y-m-d H:i:s',
    ): string {
        return TemporalCoercion::toInstant($value)
            ->withTimeZone(
                self::resolveTimeZone(
                    context: $context,
                    argument: $timeZone,
                ),
            )
            ->format($format);
    }

    #[LumiFunction('date_now')]
    public function dateNow(
        #[Context]
        RuntimeContextInterface $context,
        string $format = 'Y-m-d H:i:s',
    ): string {
        return TemporalCoercion::format(
            value: $this->clock->now(),
            pattern: $format,
            timeZone: self::resolveDirectiveTimeZone($context),
        );
    }

    #[LumiFunction('date_today')]
    public function dateToday(
        #[Context]
        RuntimeContextInterface $context,
    ): string {
        return TemporalCoercion::format(
            value: $this->clock->now(),
            pattern: 'Y-m-d',
            timeZone: self::resolveDirectiveTimeZone($context),
        );
    }

    #[LumiFunction('now')]
    public function now(): string
    {
        return (string) $this->clock->now()->toUnixTimestamp();
    }

    #[LumiFunction('time_now')]
    public function timeNow(
        #[Context]
        RuntimeContextInterface $context,
        string $format = 'H:i:s',
    ): string {
        return TemporalCoercion::format(
            value: $this->clock->now(),
            pattern: $format,
            timeZone: self::resolveDirectiveTimeZone($context),
        );
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

    /**
     * @throws TemporalException
     */
    private static function resolveTimeZone(
        RuntimeContextInterface $context,
        TimeZoneInterface|string|null $argument,
    ): TimeZoneInterface {
        if ($argument instanceof TimeZoneInterface) {
            return $argument;
        }

        if (\is_string($argument)) {
            return TimeZone::parse($argument);
        }

        return self::resolveDirectiveTimeZone($context) ?? TimeZone::parse(\date_default_timezone_get());
    }

    /**
     * @throws TemporalException
     */
    private static function resolveDirectiveTimeZone(
        RuntimeContextInterface $context,
    ): ?TimeZoneInterface {
        $directive = $context->hasDirective('tuxxedo.timezone')
            ? $context->directive('tuxxedo.timezone')
            : null;

        return $directive !== null
            ? TimeZone::parse((string) $directive)
            : null;
    }
}
