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

namespace Tuxxedo\Mail\Signer\Dkim;

class HeaderCanonicalizer
{
    public static function canonicalize(
        string $header,
        DkimCanonicalization $mode,
    ): string {
        return match ($mode) {
            DkimCanonicalization::SIMPLE => self::simple($header),
            DkimCanonicalization::RELAXED => self::relaxed($header),
        };
    }

    private static function simple(
        string $header,
    ): string {
        return $header;
    }

    private static function relaxed(
        string $header,
    ): string {
        $unfolded = \preg_replace('/\r\n([ \t])/', '$1', $header) ?? $header;

        $colonPos = \strpos($unfolded, ':');

        if ($colonPos === false) {
            return \strtolower(\rtrim($unfolded));
        }

        $name = \strtolower(\rtrim(\substr($unfolded, 0, $colonPos)));
        $value = \ltrim(\substr($unfolded, $colonPos + 1));

        $value = \preg_replace('/[ \t]+/', ' ', $value) ?? $value;
        $value = \rtrim($value);

        return $name . ':' . $value;
    }
}
