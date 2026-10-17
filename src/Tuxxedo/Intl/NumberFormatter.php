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

namespace Tuxxedo\Intl;

class NumberFormatter implements NumberFormatterInterface
{
    /**
     * @var array<string, \NumberFormatter>
     */
    private array $formatters = [];

    public function format(
        int|float $value,
        LocaleInterface $locale,
        int $decimals = 0,
    ): string {
        $formatter = $this->formatters[$locale->tag] ??= self::createFormatter(
            locale: $locale,
        );

        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        $result = $formatter->format($value);

        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromNumberFormatFailure(
                tag: $locale->tag,
            );
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    /**
     * @throws TranslationException
     */
    private static function createFormatter(
        LocaleInterface $locale,
    ): \NumberFormatter {
        try {
            return new \NumberFormatter(
                locale: $locale->tag,
                style: \NumberFormatter::DECIMAL,
            );
        } catch (\ValueError) {
            throw TranslationException::fromUnsupportedLocale(
                tag: $locale->tag,
            );
        }
    }
}
