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
        return \mb_substr($value, 0, $position);
    }

    #[LumiFunction('pad')]
    public function pad(
        string $string,
        int $length,
        string $padString = ' ',
    ): string {
        return \mb_str_pad($string, $length, $padString, \STR_PAD_BOTH);
    }

    #[LumiFunction('leftPad')]
    public function leftPad(
        string $string,
        int $length,
        string $padString = ' ',
    ): string {
        return \mb_str_pad($string, $length, $padString, \STR_PAD_LEFT);
    }

    #[LumiFunction('rightPad')]
    public function rightPad(
        string $string,
        int $length,
        string $padString = ' ',
    ): string {
        return \mb_str_pad($string, $length, $padString, \STR_PAD_RIGHT);
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
}
