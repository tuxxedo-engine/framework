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

namespace Tuxxedo\View\Lumi\Library\Standard\Filter;

use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslationException;
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
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class DateFilters
{
    /**
     * @var array<string, HumanizerInterface>
     */
    private array $localizedHumanizers = [];

    public function __construct(
        private readonly HumanizerInterface $humanizer,
    ) {
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
