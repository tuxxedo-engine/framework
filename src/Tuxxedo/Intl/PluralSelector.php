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

class PluralSelector
{
    /**
     * @var array<string, \MessageFormatter>
     */
    private array $formatters = [];

    /**
     * @throws TranslationException
     */
    public function select(
        LocaleInterface $locale,
        int $count,
    ): CldrCategory {
        $formatter = $this->formatters[$locale->tag] ??= new \MessageFormatter(
            locale: $locale->tag,
            pattern: '{n, plural, zero {zero} one {one} two {two} few {few} many {many} other {other}}',
        );

        $result = $formatter->format(
            values: [
                'n' => $count,
            ],
        );

        if (!\is_string($result)) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromPluralSelectionFailure(
                locale: $locale,
                count: $count,
            );
            // @codeCoverageIgnoreEnd
        }

        return CldrCategory::from($result);
    }
}
