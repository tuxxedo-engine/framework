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

use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Humanizer\Humanizer;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTimeInterface;
use Tuxxedo\Temporal\SystemClock;
use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;

class DateFilters
{
    private readonly Humanizer $humanizer;

    public function __construct(
        ?Humanizer $humanizer = null,
        ?ClockInterface $clock = null,
    ) {
        $this->humanizer = $humanizer ?? new Humanizer($clock ?? new SystemClock());
    }

    #[LumiFilter('date_ago')]
    public function dateAgo(
        InstantInterface|\DateTimeInterface|int|string $value,
    ): string {
        return $this->humanizer->diffForHumans(TemporalCoercion::toInstant($value));
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
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return TemporalCoercion::format(
            value: $value,
            pattern: 'l, F j, Y g:i A',
        );
    }

    #[LumiFilter('date_short')]
    public function dateShort(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return TemporalCoercion::format(
            value: $value,
            pattern: 'Y-m-d H:i',
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
}
