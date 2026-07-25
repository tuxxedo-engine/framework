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

namespace Tuxxedo\Security\Crypto;

class Base64Url
{
    public static function encode(
        string $bytes,
    ): string {
        return \rtrim(\strtr(\base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * @throws CryptoException
     */
    public static function decode(
        string $segment,
    ): string {
        $normalized = \strtr($segment, '-_', '+/');
        $padding = \strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= \str_repeat('=', 4 - $padding);
        }

        $decoded = \base64_decode($normalized, strict: true);

        if ($decoded === false) {
            throw CryptoException::fromInvalidBase64(
                segment: $segment,
            );
        }

        return $decoded;
    }
}
