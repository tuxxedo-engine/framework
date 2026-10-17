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

use Tuxxedo\Intl\DateFormatter;
use Tuxxedo\Intl\DateFormatterInterface;
use Tuxxedo\Intl\DateStyle;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\Intl\Transliteration;
use Tuxxedo\Intl\Transliterator;
use Tuxxedo\Intl\TransliteratorInterface;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTimeInterface;
use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;

class IntlFilters
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly DateFormatterInterface $dateFormatter = new DateFormatter(),
        private readonly TransliteratorInterface $transliterator = new Transliterator(),
    ) {
    }

    #[LumiFilter('translate', aliases: ['t'])]
    public function translate(
        string $value,
    ): string {
        return $this->translator->translate(
            key: $value,
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFilter('locale_date_long')]
    public function localeDateLong(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return $this->formatDate(
            value: $value,
            dateStyle: DateStyle::FULL,
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFilter('locale_date_short')]
    public function localeDateShort(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return $this->formatDate(
            value: $value,
            dateStyle: DateStyle::SHORT,
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFilter('ascii')]
    public function ascii(
        string $value,
    ): string {
        return $this->transliterator->transliterate($value);
    }

    /**
     * @throws TranslationException
     */
    #[LumiFilter('romanize')]
    public function romanize(
        string $value,
    ): string {
        return $this->transliterator->transliterate(
            value: $value,
            transliteration: Transliteration::LATIN,
        );
    }

    /**
     * @throws TranslationException
     */
    private function formatDate(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        DateStyle $dateStyle,
    ): string {
        return $this->dateFormatter->format(
            value: TemporalCoercion::toDateTime($value),
            locale: $this->translator->locale,
            dateStyle: $value instanceof LocalTimeInterface
                ? DateStyle::NONE
                : $dateStyle,
            timeStyle: $value instanceof LocalDateInterface
                ? DateStyle::NONE
                : DateStyle::SHORT,
        );
    }
}
