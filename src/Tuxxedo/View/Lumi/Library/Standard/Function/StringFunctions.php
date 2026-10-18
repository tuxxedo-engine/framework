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

class StringFunctions
{
    /**
     * @param string[] $value
     */
    #[LumiFunction('join')]
    public function join(
        array $value,
        string $separator,
    ): string {
        return \join($separator, $value);
    }

    /**
     * @param non-empty-string $separator
     * @return string[]
     */
    #[LumiFunction('split')]
    public function split(
        string $value,
        string $separator,
    ): array {
        return \explode($separator, $value);
    }

    /**
     * @param positive-int $times
     */
    #[LumiFunction('repeat')]
    public function repeat(
        string $string,
        int $times,
    ): string {
        return \str_repeat($string, $times);
    }

    /**
     * @param string|string[] $search
     * @param string|string[] $replace
     */
    #[LumiFunction('replace')]
    public function replace(
        string $subject,
        string|array $search,
        string|array $replace,
    ): string {
        return \str_replace($search, $replace, $subject);
    }

    /**
     * @param positive-int $position
     */
    #[LumiFunction('truncate', aliases: ['cut'])]
    public function truncate(
        string $value,
        int $position,
    ): string {
        return \join(
            '',
            \array_slice(self::graphemes($value), 0, $position),
        );
    }

    #[LumiFunction('pad')]
    public function pad(
        string $string,
        int $length,
        string $padString = ' ',
    ): string {
        return self::padGraphemes(
            string: $string,
            length: $length,
            padString: $padString,
            mode: \STR_PAD_BOTH,
        );
    }

    #[LumiFunction('left_pad')]
    public function leftPad(
        string $string,
        int $length,
        string $padString = ' ',
    ): string {
        return self::padGraphemes(
            string: $string,
            length: $length,
            padString: $padString,
            mode: \STR_PAD_LEFT,
        );
    }

    #[LumiFunction('right_pad')]
    public function rightPad(
        string $string,
        int $length,
        string $padString = ' ',
    ): string {
        return self::padGraphemes(
            string: $string,
            length: $length,
            padString: $padString,
            mode: \STR_PAD_RIGHT,
        );
    }

    #[LumiFunction('number')]
    public function number(
        int|float $number,
        int $decimals = 0,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ',',
    ): string {
        return \number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
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

    private static function padGraphemes(
        string $string,
        int $length,
        string $padString,
        int $mode,
    ): string {
        $missing = $length - \sizeof(self::graphemes($string));
        $padGraphemes = self::graphemes($padString);

        if ($missing <= 0 || $padGraphemes === []) {
            return $string;
        }

        $left = match ($mode) {
            \STR_PAD_LEFT => $missing,
            \STR_PAD_BOTH => \intdiv($missing, 2),
            default => 0,
        };

        return self::repeatGraphemes($padGraphemes, $left) .
            $string .
            self::repeatGraphemes($padGraphemes, $missing - $left);
    }

    /**
     * @param non-empty-list<string> $graphemes
     */
    private static function repeatGraphemes(
        array $graphemes,
        int $count,
    ): string {
        $result = '';

        for ($i = 0; $i < $count; $i++) {
            $result .= $graphemes[$i % \sizeof($graphemes)];
        }

        return $result;
    }
}
