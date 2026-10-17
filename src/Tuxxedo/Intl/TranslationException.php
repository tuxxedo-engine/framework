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

use Tuxxedo\Exception;

class TranslationException extends Exception
{
    public static function fromInvalidBcp47(
        string $input,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to parse %s as a BCP 47 locale tag',
                self::formatQuoted($input),
            ),
        );
    }

    public static function fromMissingKey(
        string $key,
        LocaleInterface $locale,
    ): self {
        return new self(
            message: \sprintf(
                'No translation found for key %s in locale %s',
                self::formatQuoted($key),
                self::formatQuoted($locale->tag),
            ),
        );
    }

    public static function fromMalformedCatalogue(
        string $file,
    ): self {
        return new self(
            message: \sprintf(
                'Catalogue file %s did not return an array',
                self::formatQuoted($file),
            ),
        );
    }

    public static function fromNonStringKey(
        string $file,
    ): self {
        return new self(
            message: \sprintf(
                'Catalogue file %s contains a non-string key',
                self::formatQuoted($file),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromPluralSelectionFailure(
        LocaleInterface $locale,
        int $count,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to select a plural category for %d in locale %s',
                $count,
                self::formatQuoted($locale->tag),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromDisplayNameFailure(
        string $tag,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to resolve a display name for locale %s',
                self::formatQuoted($tag),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromCollationFailure(
        string $tag,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to collate strings for locale %s',
                self::formatQuoted($tag),
            ),
        );
    }

    public static function fromUnsupportedLocale(
        string $tag,
    ): self {
        return new self(
            message: \sprintf(
                'ICU has no data for locale %s',
                self::formatQuoted($tag),
            ),
        );
    }

    public static function fromInvalidCurrency(
        string $currency,
    ): self {
        return new self(
            message: \sprintf(
                'Currency %s is not a three-letter ISO 4217 code',
                self::formatQuoted($currency),
            ),
        );
    }

    public static function fromUnknownTimeZone(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Unknown time zone %s',
                self::formatQuoted($name),
            ),
        );
    }

    public static function fromInvalidCountry(
        string $country,
    ): self {
        return new self(
            message: \sprintf(
                'Country %s is not a two-letter ISO 3166-1 code',
                self::formatQuoted($country),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromUnavailableTimeZoneData(): self
    {
        return new self(
            message: 'ICU time zone data (zoneStrings / metaZones) is not available',
        );
    }

    public static function fromMissingCurrency(): self
    {
        return new self(
            message: 'No currency given and the "tuxxedo.currency" directive is not set',
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromNumberFormatFailure(
        string $tag,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to format a number for locale %s',
                self::formatQuoted($tag),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromDateFormatFailure(
        string $tag,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to format a date for locale %s',
                self::formatQuoted($tag),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromUnavailableTransliteration(
        string $rules,
    ): self {
        return new self(
            message: \sprintf(
                'ICU has no transliterator for %s',
                self::formatQuoted($rules),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromTransliterationFailure(
        string $input,
        string $rules,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to transliterate %s using %s',
                self::formatQuoted($input),
                self::formatQuoted($rules),
            ),
        );
    }

    public static function fromNonStringValue(
        string $file,
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Catalogue file %s has a non-string value at key %s',
                self::formatQuoted($file),
                self::formatQuoted($key),
            ),
        );
    }

    public static function fromEmptyKeySegment(
        string $file,
    ): self {
        return new self(
            message: \sprintf(
                'Catalogue file %s contains a dotted key with an empty segment',
                self::formatQuoted($file),
            ),
        );
    }

    public static function fromKeyCollision(
        string $file,
        string $segment,
    ): self {
        return new self(
            message: \sprintf(
                'Catalogue file %s defines key segment %s both as a leaf and as a nested branch with conflicting values',
                self::formatQuoted($file),
                self::formatQuoted($segment),
            ),
        );
    }

    public static function fromMissingPluralSibling(
        string $key,
        CldrCategory $category,
        LocaleInterface $locale,
    ): self {
        return new self(
            message: \sprintf(
                'No translation found for key %s category %s in locale %s',
                self::formatQuoted($key),
                self::formatQuoted($category->value),
                self::formatQuoted($locale->tag),
            ),
        );
    }
}
