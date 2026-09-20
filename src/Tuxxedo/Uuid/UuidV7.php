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

namespace Tuxxedo\Uuid;

class UuidV7
{
    public static function generate(): Uuid
    {
        $unixMs = (int) (\microtime(true) * 1000);

        $timestamp = \pack('J', $unixMs);
        $random = \random_bytes(10);

        $bytes = \substr($timestamp, 2, 6) . $random;

        $bytes[6] = \chr((\ord($bytes[6]) & 0x0f) | 0x70);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3f) | 0x80);

        return Uuid::fromBytes(
            bytes: $bytes,
        );
    }
}
