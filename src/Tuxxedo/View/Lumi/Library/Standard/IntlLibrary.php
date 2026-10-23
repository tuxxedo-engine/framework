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

use Tuxxedo\Intl\Collator;
use Tuxxedo\Intl\CollatorInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\LocaleDirection;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\NumberFormatter;
use Tuxxedo\Intl\NumberFormatterInterface;
use Tuxxedo\Intl\Temporal\DateFormatter;
use Tuxxedo\Intl\Temporal\DateFormatterInterface;
use Tuxxedo\Intl\Temporal\DateStyle;
use Tuxxedo\Intl\Temporal\TimeZoneEntryInterface;
use Tuxxedo\Intl\Temporal\TimeZoneGroupInterface;
use Tuxxedo\Intl\Temporal\TimeZoneList;
use Tuxxedo\Intl\Temporal\TimeZoneListInterface;
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
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class IntlLibrary
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CollatorInterface $collator = new Collator(),
        private readonly NumberFormatterInterface $numberFormatter = new NumberFormatter(),
        private readonly DateFormatterInterface $dateFormatter = new DateFormatter(),
        private readonly TransliteratorInterface $transliterator = new Transliterator(),
        private readonly TimeZoneListInterface $timeZoneList = new TimeZoneList(),
    ) {
    }

    /**
     * @param array<string, scalar|null> $replacements
     *
     * @throws TranslationException
     */
    #[LumiFunction('translate', aliases: ['_', 't'])]
    #[LumiFilter('translate', aliases: ['t'])]
    public function translate(
        #[Context]
        RuntimeContextInterface $context,
        string $key,
        array $replacements = [],
        ?string $locale = null,
    ): string {
        return $this->translator->translate(
            key: $key,
            replacements: $replacements,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
        );
    }

    /**
     * @param array<string, scalar|null> $replacements
     *
     * @throws TranslationException
     */
    #[LumiFunction('plural')]
    #[LumiFilter('plural')]
    public function plural(
        #[Context]
        RuntimeContextInterface $context,
        string $key,
        int $count,
        array $replacements = [],
        ?string $locale = null,
    ): string {
        return $this->translator->plural(
            key: $key,
            count: $count,
            replacements: $replacements,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFunction('locale')]
    public function locale(
        #[Context]
        RuntimeContextInterface $context,
        ?string $locale = null,
    ): LocaleInterface {
        return $this->resolveLocale(
            context: $context,
            locale: $locale,
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFunction('locale_is_rtl')]
    public function localeIsRtl(
        #[Context]
        RuntimeContextInterface $context,
        ?string $locale = null,
    ): bool {
        return $this->resolveLocale(
            context: $context,
            locale: $locale,
        )->direction === LocaleDirection::RTL;
    }

    /**
     * @throws TranslationException
     */
    #[LumiFunction('locale_is_ltr')]
    public function localeIsLtr(
        #[Context]
        RuntimeContextInterface $context,
        ?string $locale = null,
    ): bool {
        return $this->resolveLocale(
            context: $context,
            locale: $locale,
        )->direction === LocaleDirection::LTR;
    }

    /**
     * @return 'ltr'|'rtl'
     *
     * @throws TranslationException
     */
    #[LumiFunction('locale_direction')]
    public function localeDirection(
        #[Context]
        RuntimeContextInterface $context,
        ?string $locale = null,
    ): string {
        return $this->resolveLocale(
            context: $context,
            locale: $locale,
        )->direction->value;
    }

    /**
     * @return list<LocaleInterface>
     *
     * @throws TranslationException
     */
    #[LumiFunction('locale_all')]
    public function localeAll(): array
    {
        $entries = [];
        $neutral = Locale::parse('und');

        foreach ($this->translator->supportedLocales as $locale) {
            $entries[] = [
                $this->transliterator->transliterate(
                    value: $locale->displayName,
                    transliteration: Transliteration::LATIN,
                ),
                $locale,
            ];
        }

        \usort(
            $entries,
            fn (array $a, array $b): int => $this->collator->compare(
                first: $a[0],
                second: $b[0],
                locale: $neutral,
            ),
        );

        return \array_column($entries, 1);
    }

    /**
     * @param array<mixed> $array
     * @return array<mixed>
     *
     * @throws TranslationException
     */
    #[LumiFunction('locale_sort')]
    #[LumiFilter('locale_sort')]
    public function localeSort(
        #[Context]
        RuntimeContextInterface $context,
        array $array,
        ?string $locale = null,
    ): array {
        return $this->collator->sort(
            values: $array,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
        );
    }

    /**
     * @param array<mixed> $array
     * @return array<mixed>
     *
     * @throws TranslationException
     */
    #[LumiFunction('locale_ksort')]
    #[LumiFilter('locale_ksort')]
    public function localeKsort(
        #[Context]
        RuntimeContextInterface $context,
        array $array,
        ?string $locale = null,
    ): array {
        return $this->collator->sortKeys(
            values: $array,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFunction('locale_number')]
    #[LumiFilter('locale_number')]
    public function localeNumber(
        #[Context]
        RuntimeContextInterface $context,
        int|float $number,
        int $decimals = 0,
        ?string $locale = null,
    ): string {
        return $this->numberFormatter->format(
            value: $number,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
            decimals: $decimals,
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFunction('locale_currency', aliases: ['currency'])]
    #[LumiFilter('locale_currency', aliases: ['currency'])]
    public function localeCurrency(
        #[Context]
        RuntimeContextInterface $context,
        int|float $amount,
        ?string $currency = null,
        ?string $locale = null,
    ): string {
        return $this->numberFormatter->formatCurrency(
            amount: $amount,
            currency: self::resolveCurrency(
                context: $context,
                currency: $currency,
            ),
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
        );
    }

    /**
     * @throws TranslationException
     * @throws TemporalException
     */
    #[LumiFunction('locale_date_full')]
    #[LumiFilter('locale_date_full')]
    public function localeDateFull(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        ?string $locale = null,
    ): string {
        return $this->formatDate(
            value: $value,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
            timeZone: self::resolveDirectiveTimeZone($context),
            dateStyle: DateStyle::FULL,
        );
    }

    /**
     * @throws TranslationException
     * @throws TemporalException
     */
    #[LumiFunction('locale_date_long')]
    #[LumiFilter('locale_date_long')]
    public function localeDateLong(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        ?string $locale = null,
    ): string {
        return $this->formatDate(
            value: $value,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
            timeZone: self::resolveDirectiveTimeZone($context),
            dateStyle: DateStyle::LONG,
        );
    }

    /**
     * @throws TranslationException
     * @throws TemporalException
     */
    #[LumiFunction('locale_date_medium')]
    #[LumiFilter('locale_date_medium')]
    public function localeDateMedium(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        ?string $locale = null,
    ): string {
        return $this->formatDate(
            value: $value,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
            timeZone: self::resolveDirectiveTimeZone($context),
            dateStyle: DateStyle::MEDIUM,
        );
    }

    /**
     * @throws TranslationException
     * @throws TemporalException
     */
    #[LumiFunction('locale_date_short')]
    #[LumiFilter('locale_date_short')]
    public function localeDateShort(
        #[Context]
        RuntimeContextInterface $context,
        InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value,
        ?string $locale = null,
    ): string {
        return $this->formatDate(
            value: $value,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
            timeZone: self::resolveDirectiveTimeZone($context),
            dateStyle: DateStyle::SHORT,
        );
    }

    /**
     * @return list<TimeZoneEntryInterface>
     *
     * @throws TranslationException
     */
    #[LumiFunction('timezone_all')]
    public function timezoneAll(
        #[Context]
        RuntimeContextInterface $context,
        ?string $country = null,
        ?string $locale = null,
    ): array {
        return $this->timeZoneList->all(
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
            country: $country,
        );
    }

    /**
     * @return list<TimeZoneGroupInterface>
     *
     * @throws TranslationException
     */
    #[LumiFunction('timezone_groups')]
    public function timezoneGroups(
        #[Context]
        RuntimeContextInterface $context,
        ?string $locale = null,
    ): array {
        return $this->timeZoneList->grouped(
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
        );
    }

    /**
     * @param list<string> $names
     * @return list<TimeZoneEntryInterface>
     *
     * @throws TranslationException
     */
    #[LumiFunction('timezone_list')]
    public function timezoneList(
        #[Context]
        RuntimeContextInterface $context,
        array $names,
        ?string $locale = null,
    ): array {
        return $this->timeZoneList->of(
            names: $names,
            locale: $this->resolveLocale(
                context: $context,
                locale: $locale,
            ),
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFunction('ascii')]
    #[LumiFilter('ascii')]
    public function ascii(
        string $value,
    ): string {
        return $this->transliterator->transliterate($value);
    }

    /**
     * @throws TranslationException
     */
    #[LumiFunction('romanize')]
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
        ?string $locale,
    ): LocaleInterface {
        if ($locale !== null) {
            return Locale::parse($locale);
        }

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
        ?string $currency,
    ): string {
        if ($currency !== null) {
            return $currency;
        }

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
