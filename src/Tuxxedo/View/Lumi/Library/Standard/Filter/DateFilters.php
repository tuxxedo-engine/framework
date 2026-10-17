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

    #[LumiFilter('date_long')]
    public function dateLong(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return TemporalCoercion::format(
            value: $value,
            pattern: 'l, F j, Y g:i A',
            timeZone: self::resolveDirectiveTimeZone($context),
        );
    }

    #[LumiFilter('date_short')]
    public function dateShort(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return TemporalCoercion::format(
            value: $value,
            pattern: 'Y-m-d H:i',
            timeZone: self::resolveDirectiveTimeZone($context),
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
