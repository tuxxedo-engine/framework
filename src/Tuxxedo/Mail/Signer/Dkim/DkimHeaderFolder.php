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

class DkimHeaderFolder
{
    private const int MAX_LINE_LENGTH = 78;
    private const int BASE64_CHUNK_LENGTH = 74;

    public static function fold(
        string $headerLine,
    ): string {
        $colonPos = \strpos($headerLine, ':');

        if ($colonPos === false) {
            return $headerLine;
        }

        $prefix = \substr($headerLine, 0, $colonPos + 1);
        $tagsPart = \ltrim(\substr($headerLine, $colonPos + 1));
        $tags = \explode('; ', $tagsPart);

        $lines = self::packTags($prefix, $tags);
        $folded = [];

        foreach ($lines as $line) {
            if (\strlen($line) <= self::MAX_LINE_LENGTH) {
                $folded[] = $line;

                continue;
            }

            $folded[] = self::foldLongTag($line);
        }

        return \join("\r\n", $folded);
    }

    /**
     * @param list<string> $tags
     * @return list<string>
     */
    private static function packTags(
        string $prefix,
        array $tags,
    ): array {
        $lines = [];
        $current = $prefix;
        $lastIndex = \sizeof($tags) - 1;

        foreach ($tags as $index => $tag) {
            $suffix = $index === $lastIndex
                ? ''
                : ';';
            $piece = ' ' . $tag . $suffix;

            if (\strlen($current . $piece) <= self::MAX_LINE_LENGTH) {
                $current .= $piece;

                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            $current = "\t" . $tag . $suffix;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private static function foldLongTag(
        string $line,
    ): string {
        $indent = "\r\n ";
        $chunks = [];
        $remaining = $line;

        while (\strlen($remaining) > self::MAX_LINE_LENGTH) {
            $chunks[] = \substr($remaining, 0, self::BASE64_CHUNK_LENGTH);
            $remaining = \substr($remaining, self::BASE64_CHUNK_LENGTH);
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return \join($indent, $chunks);
    }
}
