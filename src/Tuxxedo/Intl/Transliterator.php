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

class Transliterator implements TransliteratorInterface
{
    /**
     * @var array<string, \Transliterator>
     */
    private array $transliterators = [];

    public function transliterate(
        string $value,
        Transliteration $transliteration = Transliteration::ASCII,
    ): string {
        $transliterator = $this->transliterators[$transliteration->value] ??= self::createTransliterator(
            transliteration: $transliteration,
        );

        $result = $transliterator->transliterate($value);

        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromTransliterationFailure(
                input: $value,
                rules: $transliteration->value,
            );
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    /**
     * @throws TranslationException
     */
    private static function createTransliterator(
        Transliteration $transliteration,
    ): \Transliterator {
        $transliterator = \Transliterator::create($transliteration->value);

        if ($transliterator === null) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromUnavailableTransliteration(
                rules: $transliteration->value,
            );
            // @codeCoverageIgnoreEnd
        }

        return $transliterator;
    }
}
