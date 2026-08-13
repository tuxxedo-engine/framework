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

namespace Tuxxedo\Mail\Serializer\Render;

class EncodedWord
{
    private const int MAX_ENCODED_WORD_LENGTH = 75;

    public static function encode(
        string $value,
        string $charset = 'UTF-8',
    ): string {
        $overhead = \strlen($charset) + 7;
        $maxPayloadBytes = (int) \floor((self::MAX_ENCODED_WORD_LENGTH - $overhead) / 4) * 3;

        if ($maxPayloadBytes < 1) {
            // @codeCoverageIgnoreStart
            return self::wrap($charset, \base64_encode($value));
            // @codeCoverageIgnoreEnd
        }

        $chunks = [];
        $buffer = '';
        $characters = \mb_str_split($value, 1, $charset);

        foreach ($characters as $character) {
            $candidate = $buffer . $character;

            if (\strlen($candidate) > $maxPayloadBytes) {
                $chunks[] = self::wrap($charset, \base64_encode($buffer));
                $buffer = $character;

                continue;
            }

            $buffer = $candidate;
        }

        if ($buffer !== '') {
            $chunks[] = self::wrap($charset, \base64_encode($buffer));
        }

        return \implode("\r\n ", $chunks);
    }

    public static function encodeIfNonAscii(
        string $value,
        string $charset = 'UTF-8',
    ): string {
        if (\preg_match('/[^\x00-\x7F]/', $value) === 1) {
            return self::encode($value, $charset);
        }

        return $value;
    }

    private static function wrap(
        string $charset,
        string $encoded,
    ): string {
        return \sprintf(
            '=?%s?B?%s?=',
            $charset,
            $encoded,
        );
    }
}
