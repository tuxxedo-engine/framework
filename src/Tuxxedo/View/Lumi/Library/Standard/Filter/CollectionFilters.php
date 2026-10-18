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

namespace Tuxxedo\View\Lumi\Library\Standard\Filter;

use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;

class CollectionFilters
{
    /**
     * @param array<mixed> $value
     */
    #[LumiFilter('count', aliases: ['sizeof'])]
    public function count(
        array $value,
    ): int {
        return \sizeof($value);
    }

    /**
     * @param string|array<mixed>|\Countable $value
     */
    #[LumiFilter('length')]
    public function length(
        string|array|\Countable $value,
    ): int {
        if ($value instanceof \Countable) {
            return $value->count();
        }

        if (\is_array($value)) {
            return \sizeof($value);
        }

        $length = \preg_match_all('/\X/u', $value);

        return $length !== false
            ? $length
            : \mb_strlen($value);
    }

    /**
     * @param iterable<mixed>|string $input
     */
    #[LumiFilter('first')]
    public function first(
        iterable|string $input,
    ): mixed {
        if (\is_string($input)) {
            return self::graphemes($input)[0] ?? null;
        }

        if (\is_array($input)) {
            $key = \array_key_first($input);

            return $key !== null
                ? $input[$key]
                : null;
        }

        foreach ($input as $value) {
            return $value;
        }

        return null;
    }

    /**
     * @param iterable<mixed>|string $input
     */
    #[LumiFilter('last')]
    public function last(
        iterable|string $input,
    ): mixed {
        if (\is_string($input)) {
            $graphemes = self::graphemes($input);

            return $graphemes !== []
                ? $graphemes[\sizeof($graphemes) - 1]
                : null;
        }

        if (\is_array($input)) {
            $key = \array_key_last($input);

            return $key !== null
                ? $input[$key]
                : null;
        }

        $last = null;

        foreach ($input as $value) {
            $last = $value;
        }

        return $last;
    }

    /**
     * @param iterable<mixed>|string $input
     */
    #[LumiFilter('first_key')]
    public function firstKey(
        iterable|string $input,
    ): mixed {
        if (\is_string($input)) {
            return self::graphemes($input) !== []
                ? 0
                : null;
        }

        if (\is_array($input)) {
            return \array_key_first($input);
        }

        foreach ($input as $key => $value) {
            return $key;
        }

        return null;
    }

    /**
     * @param iterable<mixed>|string $input
     */
    #[LumiFilter('last_key')]
    public function lastKey(
        iterable|string $input,
    ): mixed {
        if (\is_string($input)) {
            $count = \sizeof(self::graphemes($input));

            return $count > 0
                ? $count - 1
                : null;
        }

        if (\is_array($input)) {
            return \array_key_last($input);
        }

        $last = null;

        foreach ($input as $key => $value) {
            $last = $key;
        }

        return $last;
    }

    /**
     * @return list<string>
     */
    private static function graphemes(
        string $value,
    ): array {
        if (\preg_match_all('/\X/u', $value, $matches) === false) {
            return \mb_str_split($value);
        }

        return $matches[0];
    }
}
