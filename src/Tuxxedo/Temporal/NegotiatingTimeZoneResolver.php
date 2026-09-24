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

namespace Tuxxedo\Temporal;

class NegotiatingTimeZoneResolver implements TimeZoneResolverInterface
{
    /**
     * @var list<TimeZoneSourceInterface>
     */
    private readonly array $sources;

    /**
     * @param list<TimeZoneSourceInterface> $sources
     */
    public function __construct(
        array $sources,
        private readonly TimeZoneInterface $fallback,
    ) {
        $this->sources = self::sortByPriority(sources: $sources);
    }

    public function currentTimeZone(): TimeZoneInterface
    {
        foreach ($this->sources as $source) {
            $detected = $source->detect();

            if ($detected !== null) {
                return $detected;
            }
        }

        return $this->fallback;
    }

    /**
     * @param list<TimeZoneSourceInterface> $sources
     *
     * @return list<TimeZoneSourceInterface>
     */
    private static function sortByPriority(
        array $sources,
    ): array {
        \usort(
            $sources,
            static fn (
                TimeZoneSourceInterface $a,
                TimeZoneSourceInterface $b,
            ): int => $a->priority->value <=> $b->priority->value,
        );

        return $sources;
    }
}
