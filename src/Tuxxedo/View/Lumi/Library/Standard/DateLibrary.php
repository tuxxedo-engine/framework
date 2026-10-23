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

namespace Tuxxedo\View\Lumi\Library\Standard;

use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\HumanizerInterface;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTimeInterface;
use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\Temporal\TemporalException;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\Temporal\TimeZoneInterface;
use Tuxxedo\View\Lumi\Library\Attribute\Context;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class DateLibrary
{
    /**
     * @var array<string, HumanizerInterface>
     */
    private array $localizedHumanizers = [];

    public function __construct(
        private readonly ClockInterface $clock,
        private readonly HumanizerInterface $humanizer,
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

    /**
     * @throws TranslationException
     */
    #[LumiFunction('date_duration')]
    #[LumiFilter('date_duration')]
    public function dateDuration(
        #[Context]
        RuntimeContextInterface $context,
        DurationInterface|int|string $value,
        string $style = 'long',
    ): string {
        return $this->localizedHumanizer($context)->formatDuration(
            duration: self::coerceDuration($value),
            style: match (\strtolower($style)) {
                'short' => HumanizedStyle::SHORT,
                'narrow' => HumanizedStyle::NARROW,
                default => HumanizedStyle::LONG,
            },
        );
    }

    #[LumiFunction('date_local')]
    #[LumiFilter('date_local')]
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

    /**
     * @throws TranslationException
     */
    #[LumiFilter('date_ago')]
    public function dateAgo(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|\DateTimeInterface|int|string $value,
    ): string {
        return $this->localizedHumanizer($context)->diffForHumans(TemporalCoercion::toInstant($value));
    }

    #[LumiFilter('date_iso')]
    public function dateIso(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        if (
            $value instanceof InstantInterface ||
            $value instanceof LocalDateInterface ||
            $value instanceof LocalTimeInterface
        ) {
            return $value->toIso8601();
        }

        return TemporalCoercion::toInstant($value)->toIso8601();
    }

    #[LumiFilter('date_full')]
    public function dateFull(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return self::formatPreset(
            context: $context,
            value: $value,
            datePattern: 'l, F j, Y',
            timePattern: 'g:i A',
        );
    }

    #[LumiFilter('date_long')]
    public function dateLong(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return self::formatPreset(
            context: $context,
            value: $value,
            datePattern: 'F j, Y',
            timePattern: 'g:i A',
        );
    }

    #[LumiFilter('date_medium')]
    public function dateMedium(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return self::formatPreset(
            context: $context,
            value: $value,
            datePattern: 'M j, Y',
            timePattern: 'g:i A',
        );
    }

    #[LumiFilter('date_short')]
    public function dateShort(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return self::formatPreset(
            context: $context,
            value: $value,
            datePattern: 'Y-m-d',
            timePattern: 'H:i',
        );
    }

    #[LumiFilter('date_utc')]
    public function dateUtc(
        InstantInterface|\DateTimeInterface|int|string $value,
    ): string {
        return TemporalCoercion::toInstant($value)
            ->withTimeZone(TimeZone::utc())
            ->format('Y-m-d H:i:s');
    }

    #[LumiFilter('time_iso')]
    public function timeIso(
        LocalTimeInterface|InstantInterface|\DateTimeInterface|int|string $value,
    ): string {
        if ($value instanceof LocalTimeInterface) {
            return $value->toIso8601();
        }

        return TemporalCoercion::format(
            value: $value,
            pattern: 'H:i:s',
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
     * @throws TranslationException
     */
    private function localizedHumanizer(
        RuntimeContextInterface $context,
    ): HumanizerInterface {
        $directive = $context->hasDirective('tuxxedo.locale')
            ? $context->directive('tuxxedo.locale')
            : null;

        if ($directive === null) {
            return $this->humanizer;
        }

        $tag = (string) $directive;

        return $this->localizedHumanizers[$tag] ??= $this->humanizer->withLocale(Locale::parse($tag));
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

    /**
     * @throws TemporalException
     */
    private static function formatPreset(
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        string $datePattern,
        string $timePattern,
    ): string {
        return TemporalCoercion::format(
            value: $value,
            pattern: match (true) {
                $value instanceof LocalDateInterface => $datePattern,
                $value instanceof LocalTimeInterface => $timePattern,
                default => $datePattern . ' ' . $timePattern,
            },
            timeZone: self::resolveDirectiveTimeZone($context),
        );
    }
}
