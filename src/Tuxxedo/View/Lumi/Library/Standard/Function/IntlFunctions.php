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

use Tuxxedo\Intl\Collator;
use Tuxxedo\Intl\CollatorInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\LocaleDirection;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\NumberFormatter;
use Tuxxedo\Intl\NumberFormatterInterface;
use Tuxxedo\Intl\TranslationException;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class IntlFunctions
{
    private ?\Transliterator $transliterator = null;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CollatorInterface $collator = new Collator(),
        private readonly NumberFormatterInterface $numberFormatter = new NumberFormatter(),
    ) {
    }

    /**
     * @param array<string, scalar|null> $replacements
     */
    #[LumiFunction('translate', aliases: ['_', 't'])]
    public function translate(
        string $key,
        array $replacements = [],
        ?string $locale = null,
    ): string {
        return $this->translator->translate(
            key: $key,
            replacements: $replacements,
            locale: $locale !== null
                ? Locale::parse($locale)
                : null,
        );
    }

    /**
     * @param array<string, scalar|null> $replacements
     */
    #[LumiFunction('plural')]
    public function plural(
        string $key,
        int $count,
        array $replacements = [],
        ?string $locale = null,
    ): string {
        return $this->translator->plural(
            key: $key,
            count: $count,
            replacements: $replacements,
            locale: $locale !== null
                ? Locale::parse($locale)
                : null,
        );
    }

    #[LumiFunction('locale')]
    public function locale(
        ?string $locale = null,
    ): LocaleInterface {
        return $locale !== null
            ? Locale::parse($locale)
            : $this->translator->locale;
    }

    #[LumiFunction('locale_is_rtl')]
    public function localeIsRtl(
        ?string $locale = null,
    ): bool {
        $direction = $locale !== null
            ? Locale::parse($locale)->direction
            : $this->translator->locale->direction;

        return $direction === LocaleDirection::RTL;
    }

    #[LumiFunction('locale_is_ltr')]
    public function localeIsLtr(
        ?string $locale = null,
    ): bool {
        $direction = $locale !== null
            ? Locale::parse($locale)->direction
            : $this->translator->locale->direction;

        return $direction === LocaleDirection::LTR;
    }

    /**
     * @return 'ltr'|'rtl'
     */
    #[LumiFunction('locale_direction')]
    public function localeDirection(
        ?string $locale = null,
    ): string {
        $direction = $locale !== null
            ? Locale::parse($locale)->direction
            : $this->translator->locale->direction;

        return $direction->value;
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

        foreach ($this->translator->supportedLocales as $locale) {
            $entries[] = [
                $this->latinSortKey(
                    name: $locale->displayName,
                ),
                $locale,
            ];
        }

        \usort(
            $entries,
            fn (array $a, array $b): int => $this->collator->compare(
                first: $a[0],
                second: $b[0],
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
    public function localeSort(
        array $array,
        ?string $locale = null,
    ): array {
        return $this->collator->sort(
            values: $array,
            locale: $locale !== null
                ? Locale::parse($locale)
                : $this->translator->locale,
        );
    }

    /**
     * @param array<mixed> $array
     * @return array<mixed>
     *
     * @throws TranslationException
     */
    #[LumiFunction('locale_ksort')]
    public function localeKsort(
        array $array,
        ?string $locale = null,
    ): array {
        return $this->collator->sortKeys(
            values: $array,
            locale: $locale !== null
                ? Locale::parse($locale)
                : $this->translator->locale,
        );
    }

    /**
     * @throws TranslationException
     */
    #[LumiFunction('locale_number')]
    public function localeNumber(
        int|float $number,
        int $decimals = 0,
        ?string $locale = null,
    ): string {
        return $this->numberFormatter->format(
            value: $number,
            locale: $locale !== null
                ? Locale::parse($locale)
                : $this->translator->locale,
            decimals: $decimals,
        );
    }

    /**
     * @throws TranslationException
     */
    private function latinSortKey(
        string $name,
    ): string {
        $this->transliterator ??= \Transliterator::create('Any-Latin');
        $latin = $this->transliterator?->transliterate($name) ?? false;

        if ($latin === false) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromTransliterationFailure(
                input: $name,
            );
            // @codeCoverageIgnoreEnd
        }

        return $latin;
    }
}
