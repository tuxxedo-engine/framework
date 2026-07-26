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

namespace Tuxxedo\Mail\Serializer\Encoding;

use Tuxxedo\Mail\Serializer\Mime\ContentTransferEncoding;

class ContentTransferEncodingSelector
{
    private const int MAX_LINE_LENGTH = 998;

    public static function selectFor(
        string $content,
        string $mimeType,
    ): ContentTransferEncoding {
        if (!\str_starts_with($mimeType, 'text/')) {
            return ContentTransferEncoding::BASE64;
        }

        if (self::hasLongLines($content)) {
            return ContentTransferEncoding::QUOTED_PRINTABLE;
        }

        if (\preg_match('/[^\x00-\x7F]/', $content) === 1) {
            return ContentTransferEncoding::EIGHT_BIT;
        }

        return ContentTransferEncoding::SEVEN_BIT;
    }

    private static function hasLongLines(
        string $content,
    ): bool {
        $normalized = \preg_replace('/\r\n|\r|\n/', "\n", $content) ?? $content;

        foreach (\explode("\n", $normalized) as $line) {
            if (\strlen($line) > self::MAX_LINE_LENGTH) {
                return true;
            }
        }

        return false;
    }
}
