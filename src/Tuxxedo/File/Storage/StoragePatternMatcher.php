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

namespace Tuxxedo\File\Storage;

class StoragePatternMatcher
{
    #[\NoDiscard]
    public static function matches(
        string $pattern,
        string $key,
    ): bool {
        return \preg_match(self::toRegex($pattern), $key) === 1;
    }

    #[\NoDiscard]
    public static function literalPrefix(
        string $pattern,
    ): string {
        $prefix = '';
        $length = \strlen($pattern);

        for ($index = 0; $index < $length; $index++) {
            $character = $pattern[$index];

            if ($character === '*' || $character === '?') {
                break;
            }

            $prefix .= $character;
        }

        return $prefix;
    }

    #[\NoDiscard]
    private static function toRegex(
        string $pattern,
    ): string {
        $regex = '';
        $length = \strlen($pattern);
        $index = 0;

        while ($index < $length) {
            $character = $pattern[$index];

            if ($character === '*') {
                if ($index + 1 < $length && $pattern[$index + 1] === '*') {
                    if ($index + 2 < $length && $pattern[$index + 2] === '/') {
                        $regex .= '(?:.*/)?';
                        $index += 3;

                        continue;
                    }

                    $regex .= '.*';
                    $index += 2;

                    continue;
                }

                $regex .= '[^/]*';

                $index++;

                continue;
            }

            if ($character === '?') {
                $regex .= '[^/]';

                $index++;

                continue;
            }

            $regex .= \preg_quote($character, '~');

            $index++;
        }

        return '~^' . $regex . '$~';
    }
}
