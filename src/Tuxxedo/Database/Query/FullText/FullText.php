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

namespace Tuxxedo\Database\Query\FullText;

use Tuxxedo\Database\SqlException;

class FullText
{
    public const int MIN_TERM_LENGTH = 3;
    public const int MAX_TERM_LENGTH = 84;
    public const string TRANSLITERATION = 'Any-Latin; Latin-ASCII; Lower()';

    /**
     * @throws SqlException
     */
    public static function normalize(
        string $text,
    ): string {
        return \join(' ', self::tokens($text));
    }

    /**
     * @return list<string>
     *
     * @throws SqlException
     */
    public static function terms(
        string $search,
    ): array {
        $terms = [];

        foreach (self::tokens($search) as $token) {
            $length = \strlen($token);

            if ($length < self::MIN_TERM_LENGTH || $length > self::MAX_TERM_LENGTH) {
                continue;
            }

            $terms[$token] = $token;
        }

        return \array_values($terms);
    }

    /**
     * @param non-empty-list<string> $columns
     */
    public static function indexName(
        string $table,
        array $columns,
    ): string {
        return $table . '_' . \join('_', $columns) . '_ft';
    }

    /**
     * @return list<string>
     *
     * @throws SqlException
     */
    private static function tokens(
        string $text,
    ): array {
        $transliterator = \Transliterator::create(self::TRANSLITERATION);

        if ($transliterator === null) {
            throw SqlException::fromFullTextTransliteratorUnavailable(); // @codeCoverageIgnore
        }

        $transliterated = $transliterator->transliterate($text);

        if ($transliterated === false) {
            throw SqlException::fromFullTextTransliteratorUnavailable(); // @codeCoverageIgnore
        }

        $tokens = \preg_split(
            pattern: '/[^a-z0-9]+/',
            subject: $transliterated,
            flags: \PREG_SPLIT_NO_EMPTY,
        );

        if ($tokens === false) {
            return []; // @codeCoverageIgnore
        }

        return $tokens;
    }
}
