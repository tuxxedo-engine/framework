<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Escaper;

class Escaper implements EscaperInterface
{
    public function html(
        string $input,
    ): string {
        return \htmlentities($input);
    }

    public function htmlComment(
        string $input,
    ): string {
        $input = $input
                |> (static fn (string $input): string => \str_replace(["\r\n", "\r"], "\n", $input))
                |> (static fn (string $input): string => \str_replace('--', '- -', $input));

        $length = \strlen($input);

        if ($length > 0 && $input[$length - 1] === '-') {
            $input .= ' ';
        }

        return $input;
    }

    public function attribute(
        string $input,
    ): string {
        return \htmlspecialchars($input, \ENT_QUOTES);
    }

    public function js(
        string $input,
    ): string {
        return \addcslashes($input, '\'');
    }

    public function url(
        string $input,
    ): string {
        return \rawurlencode($input);
    }

    public function css(
        string $input,
    ): string {
        $length = \strlen($input);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];
            $ord = \ord($char);

            $isDigit = $ord >= 48 && $ord <= 57;
            $isUpper = $ord >= 65 && $ord <= 90;
            $isLower = $ord >= 97 && $ord <= 122;

            if ($isDigit || $isUpper || $isLower) {
                $result .= $char;
            } else {
                $result .= '\\' . \strtoupper(\dechex($ord)) . ' ';
            }
        }

        return $result;
    }
}
