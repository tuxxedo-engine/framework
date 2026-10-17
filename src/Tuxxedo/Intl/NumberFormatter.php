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
    private const string ROOT = 'root';

    /**
     * @var array<string, \NumberFormatter>
     */
    private array $formatters = [];

    public function format(
        int|float $value,
        int $decimals = 0,
        ?LocaleInterface $locale = null,
    ): string {
        $tag = $locale->tag ?? self::ROOT;
        $formatter = $this->formatters[$tag] ??= new \NumberFormatter(
            locale: $tag,
            style: \NumberFormatter::DECIMAL,
        );

        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        $result = $formatter->format($value);

        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromNumberFormatFailure(
                tag: $tag,
            );
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }
}
