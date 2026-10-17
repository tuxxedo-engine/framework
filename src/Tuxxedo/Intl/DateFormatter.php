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

class DateFormatter implements DateFormatterInterface
{
    public function format(
        \DateTimeInterface $value,
        LocaleInterface $locale,
        DateStyle $dateStyle,
        DateStyle $timeStyle,
    ): string {
        $result = \IntlDateFormatter::formatObject(
            datetime: $value,
            format: [
                self::toIcuStyle($dateStyle),
                self::toIcuStyle($timeStyle),
            ],
            locale: $locale->tag,
        );

        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromDateFormatFailure(
                tag: $locale->tag,
            );
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    private static function toIcuStyle(
        DateStyle $style,
    ): int {
        return match ($style) {
            DateStyle::NONE => \IntlDateFormatter::NONE,
            DateStyle::SHORT => \IntlDateFormatter::SHORT,
            DateStyle::MEDIUM => \IntlDateFormatter::MEDIUM,
            DateStyle::LONG => \IntlDateFormatter::LONG,
            DateStyle::FULL => \IntlDateFormatter::FULL,
        };
    }
}
