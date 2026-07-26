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
    public static function encode(
        string $value,
        string $charset = 'UTF-8',
    ): string {
        return \sprintf(
            '=?%s?B?%s?=',
            $charset,
            \base64_encode($value),
        );
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
}
