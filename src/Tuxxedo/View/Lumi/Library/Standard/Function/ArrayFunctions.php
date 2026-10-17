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

use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class ArrayFunctions
{
    /**
     * @param mixed[] $array
     * @return mixed[]
     */
    #[LumiFunction('ksort')]
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
