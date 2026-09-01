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

class Encoder
{
    private const int BASE64_LINE_LENGTH = 76;

    public static function encode(
        string $content,
        ContentTransferEncoding $encoding,
    ): string {
        return match ($encoding) { // @codeCoverageIgnore
            ContentTransferEncoding::SEVEN_BIT => self::sevenBit($content),
            ContentTransferEncoding::EIGHT_BIT => self::eightBit($content),
            ContentTransferEncoding::QUOTED_PRINTABLE => self::quotedPrintable($content),
            ContentTransferEncoding::BASE64 => self::base64($content),
        };
    }

    public static function sevenBit(
        string $content,
    ): string {
        return self::normalizeLineEndings($content);
    }

    public static function eightBit(
        string $content,
    ): string {
        return self::normalizeLineEndings($content);
    }

    public static function quotedPrintable(
        string $content,
    ): string {
        return \quoted_printable_encode(
            self::normalizeLineEndings($content),
        );
    }

    public static function base64(
        string $content,
    ): string {
        return \join(
            "\r\n",
            \str_split(
                \base64_encode($content),
                self::BASE64_LINE_LENGTH,
            ),
        );
    }

    private static function normalizeLineEndings(
        string $content,
    ): string {
        return \preg_replace('/\r\n|\r|\n/', "\r\n", $content) ?? $content;
    }
}
