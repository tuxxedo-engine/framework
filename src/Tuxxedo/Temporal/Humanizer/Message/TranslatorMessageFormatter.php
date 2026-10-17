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

use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\RelativeDirection;
use Tuxxedo\Temporal\Humanizer\RelativeUnit;

class TranslatorMessageFormatter implements MessageFormatterInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ?LocaleInterface $locale = null,
    ) {
    }

    public function withLocale(
        ?LocaleInterface $locale,
    ): static {
        return clone (
            $this,
            [
                'locale' => $locale,
            ],
        );
    }

    public function forJustNow(): string
    {
        return $this->translator->translate(
            key: 'temporal.humanizer.justNow',
            locale: $this->locale,
        );
    }

    public function forRelativeMoment(
        int $count,
        RelativeUnit $unit,
        RelativeDirection $direction,
    ): string {
        $unitName = match ($unit) { // @codeCoverageIgnore
            RelativeUnit::SECONDS => 'second',
            RelativeUnit::MINUTES => 'minute',
            RelativeUnit::HOURS => 'hour',
            RelativeUnit::DAYS => 'day',
            RelativeUnit::WEEKS => 'week',
            RelativeUnit::MONTHS => 'month',
            RelativeUnit::YEARS => 'year',
        };

        $prefix = $direction === RelativeDirection::PAST
            ? 'temporal.humanizer.relative.past.'
            : 'temporal.humanizer.relative.future.';

        return $this->translator->plural(
            key: $prefix . $unitName,
            locale: $this->locale,
            count: $count,
            replacements: [
                'count' => $count,
            ],
        );
    }

    public function forDuration(
        array $parts,
        HumanizedStyle $style,
    ): string {
        $partKey = match ($style) { // @codeCoverageIgnore
            HumanizedStyle::LONG => 'temporal.humanizer.duration.long.part',
            HumanizedStyle::SHORT => 'temporal.humanizer.duration.short.part',
            HumanizedStyle::NARROW => 'temporal.humanizer.duration.narrow.part',
        };

        $separatorKey = match ($style) { // @codeCoverageIgnore
            HumanizedStyle::LONG => 'temporal.humanizer.duration.long.separator',
            HumanizedStyle::SHORT => 'temporal.humanizer.duration.short.separator',
            HumanizedStyle::NARROW => 'temporal.humanizer.duration.narrow.separator',
        };

        $rendered = [];

        foreach ($parts as [$count, $unit]) {
            $rendered[] = $style === HumanizedStyle::LONG
                ? $this->translator->translate(
                    key: $partKey,
                    locale: $this->locale,
                    replacements: [
                        'count' => $count,
                        'word' => $this->unitWord(
                            unit: $unit,
                            count: $count,
                        ),
                    ],
                )
                : $this->translator->translate(
                    key: $partKey,
                    locale: $this->locale,
                    replacements: [
                        'count' => $count,
                        'letter' => $this->shortUnitLetter(
                            unit: $unit,
                        ),
                    ],
                );
        }

        $separator = $this->translator->translate(
            key: $separatorKey,
            locale: $this->locale,
        );

        return \join($separator, $rendered);
    }

    public function forZeroDuration(
        HumanizedStyle $style,
    ): string {
        $key = match ($style) { // @codeCoverageIgnore
            HumanizedStyle::LONG => 'temporal.humanizer.duration.zero.long',
            HumanizedStyle::SHORT, HumanizedStyle::NARROW => 'temporal.humanizer.duration.zero.short',
        };

        return $this->translator->translate(
            key: $key,
            locale: $this->locale,
        );
    }

    private function unitWord(
        RelativeUnit $unit,
        int $count,
    ): string {
        $key = match ($unit) { // @codeCoverageIgnore
            RelativeUnit::SECONDS => 'temporal.humanizer.unit.second',
            RelativeUnit::MINUTES => 'temporal.humanizer.unit.minute',
            RelativeUnit::HOURS => 'temporal.humanizer.unit.hour',
            RelativeUnit::DAYS => 'temporal.humanizer.unit.day',
            RelativeUnit::WEEKS => 'temporal.humanizer.unit.week',
            RelativeUnit::MONTHS => 'temporal.humanizer.unit.month',
            RelativeUnit::YEARS => 'temporal.humanizer.unit.year',
        };

        return $this->translator->plural(
            key: $key,
            locale: $this->locale,
            count: $count,
        );
    }

    private function shortUnitLetter(
        RelativeUnit $unit,
    ): string {
        $key = match ($unit) { // @codeCoverageIgnore
            RelativeUnit::SECONDS => 'temporal.humanizer.unitLetter.second',
            RelativeUnit::MINUTES => 'temporal.humanizer.unitLetter.minute',
            RelativeUnit::HOURS => 'temporal.humanizer.unitLetter.hour',
            RelativeUnit::DAYS => 'temporal.humanizer.unitLetter.day',
            RelativeUnit::WEEKS => 'temporal.humanizer.unitLetter.week',
            RelativeUnit::MONTHS => 'temporal.humanizer.unitLetter.month',
            RelativeUnit::YEARS => 'temporal.humanizer.unitLetter.year',
        };

        return $this->translator->translate(
            key: $key,
            locale: $this->locale,
        );
    }
}
