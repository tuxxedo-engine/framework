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

namespace Tuxxedo\View\Lumi\Library\Standard;

use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class CollectionLibrary
{
    /**
     * @param mixed[] $array
     * @return mixed[]
     */
    #[LumiFunction('ksort')]
    #[LumiFilter('ksort')]
    public function ksort(
        array $array,
    ): array {
        \ksort($array);

        return $array;
    }

    /**
     * @param mixed[] $array
     * @return mixed[]
     */
    #[LumiFunction('sort')]
    #[LumiFilter('sort')]
    public function sort(
        array $array,
    ): array {
        \asort($array);

        return $array;
    }

    /**
     * @param mixed[]|string $input
     * @return mixed[]|string
     */
    #[LumiFunction('reverse')]
    #[LumiFilter('reverse')]
    public function reverse(
        array|string $input,
    ): array|string {
        if (\is_array($input)) {
            return \array_reverse(
                array: $input,
                preserve_keys: true,
            );
        }

        return \join(
            '',
            \array_reverse(self::graphemes($input)),
        );
    }

    /**
     * @param mixed[]|string $input
     * @return mixed[]|string
     */
    #[LumiFunction('slice')]
    #[LumiFilter('slice')]
    public function slice(
        array|string $input,
        int $offset,
        ?int $length = null,
    ): array|string {
        if (\is_array($input)) {
            return \array_slice($input, $offset, $length);
        }

        return \join(
            '',
            \array_slice(self::graphemes($input), $offset, $length),
        );
    }

    /**
     * @param iterable<mixed>|string $input
     */
    #[LumiFunction('first')]
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
    #[LumiFunction('last')]
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
    #[LumiFunction('first_key')]
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
    #[LumiFunction('last_key')]
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
