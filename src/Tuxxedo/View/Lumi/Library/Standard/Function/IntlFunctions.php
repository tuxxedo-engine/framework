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

use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class IntlFunctions
{
    public function __construct(
        private readonly TranslatorInterface $translator,
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
}
