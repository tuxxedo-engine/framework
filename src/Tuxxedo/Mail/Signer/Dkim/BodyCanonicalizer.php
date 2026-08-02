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

class BodyCanonicalizer
{
    public static function canonicalize(
        string $body,
        DkimCanonicalization $mode,
    ): string {
        return match ($mode) {
            DkimCanonicalization::SIMPLE => self::simple($body),
            DkimCanonicalization::RELAXED => self::relaxed($body),
        };
    }

    private static function simple(
        string $body,
    ): string {
        $trimmed = \preg_replace('/(\r\n)+$/', '', $body) ?? $body;

        return $trimmed . "\r\n";
    }

    private static function relaxed(
        string $body,
    ): string {
        $lines = \explode("\r\n", $body);

        $processed = [];

        foreach ($lines as $line) {
            $collapsed = \preg_replace('/[ \t]+/', ' ', $line) ?? $line;
            $processed[] = \rtrim($collapsed);
        }

        $joined = \implode("\r\n", $processed);
        $joined = \preg_replace('/(\r\n)+$/', '', $joined) ?? $joined;

        return $joined . "\r\n";
    }
}
