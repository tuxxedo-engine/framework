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
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\NumberFormatter;
use Tuxxedo\Intl\NumberFormatterInterface;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\Intl\Transliteration;
use Tuxxedo\Intl\Transliterator;
use Tuxxedo\Intl\TransliteratorInterface;
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

class IntlFilters
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly DateFormatterInterface $dateFormatter = new DateFormatter(),
        private readonly TransliteratorInterface $transliterator = new Transliterator(),
        private readonly NumberFormatterInterface $numberFormatter = new NumberFormatter(),
    ) {
    }

    /**
     * @throws TranslationException
     */
    #[LumiFilter('translate', aliases: ['t'])]
    public function translate(
        #[Context]
        RuntimeContextInterface $context,
        string $value,
    ): string {
        return $this->translator->translate(
            key: $value,
            locale: $this->resolveLocale($context),
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFilter('currency')]
    public function currency(
        #[Context]
        RuntimeContextInterface $context,
        int|float $value,
    ): string {
        return $this->numberFormatter->formatCurrency(
            amount: $value,
            currency: self::resolveCurrency($context),
            locale: $this->resolveLocale($context),
        );
    }

    /**
     * @throws TranslationException
     * @throws TemporalException
     */
    #[LumiFilter('locale_date_long')]
    public function localeDateLong(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return $this->formatDate(
            value: $value,
            locale: $this->resolveLocale($context),
            timeZone: self::resolveDirectiveTimeZone($context),
            dateStyle: DateStyle::FULL,
        );
    }

    /**
     * @throws TranslationException
     * @throws TemporalException
     */
    #[LumiFilter('locale_date_short')]
    public function localeDateShort(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
    ): string {
        return $this->formatDate(
            value: $value,
            locale: $this->resolveLocale($context),
            timeZone: self::resolveDirectiveTimeZone($context),
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
    private function resolveLocale(
        RuntimeContextInterface $context,
    ): LocaleInterface {
        $directive = $context->hasDirective('tuxxedo.locale')
            ? $context->directive('tuxxedo.locale')
            : null;

        if ($directive !== null) {
            return Locale::parse((string) $directive);
        }

        return $this->translator->locale;
    }

    /**
     * @throws TranslationException
     */
    private static function resolveCurrency(
        RuntimeContextInterface $context,
    ): string {
        $directive = $context->hasDirective('tuxxedo.currency')
            ? $context->directive('tuxxedo.currency')
            : null;

        if ($directive === null) {
            throw TranslationException::fromMissingCurrency();
        }

        return (string) $directive;
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
     * @throws TranslationException
     */
    private function formatDate(
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        LocaleInterface $locale,
        ?TimeZoneInterface $timeZone,
        DateStyle $dateStyle,
    ): string {
        return $this->dateFormatter->format(
            value: TemporalCoercion::toDateTime(
                value: $value,
                timeZone: $timeZone,
            ),
            locale: $locale,
            dateStyle: $value instanceof LocalTimeInterface
                ? DateStyle::NONE
                : $dateStyle,
            timeStyle: $value instanceof LocalDateInterface
                ? DateStyle::NONE
                : DateStyle::SHORT,
        );
    }
}
