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

use Tuxxedo\Mail\Serializer\SerializedMessageInterface;

class DkimSigningInput
{
    public static function build(
        SerializedMessageInterface $serialized,
        DkimSignatureTag $tag,
    ): string {
        $parsed = self::parseHeaders($serialized->headers);

        $input = '';

        foreach ($tag->signedHeaders as $name) {
            $raw = self::findHeader($parsed, $name);
            $input .= HeaderCanonicalizer::canonicalize($raw, $tag->headerCanonicalization) . "\r\n";
        }

        $dkimHeaderLine = 'DKIM-Signature: ' . $tag->toHeaderValue();
        $input .= HeaderCanonicalizer::canonicalize($dkimHeaderLine, $tag->headerCanonicalization);

        return $input;
    }

    /**
     * @return list<array{name: string, raw: string}>
     */
    private static function parseHeaders(
        string $headerBlock,
    ): array {
        $lines = \explode("\r\n", $headerBlock);
        $result = [];
        $current = null;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if ($line[0] === ' ' || $line[0] === "\t") {
                if ($current !== null) {
                    $current['raw'] .= "\r\n" . $line;
                }

                continue;
            }

            if ($current !== null) {
                $result[] = $current;
            }

            $colonPos = \strpos($line, ':');
            $name = $colonPos === false
                ? $line
                : \substr($line, 0, $colonPos);

            $current = [
                'name' => $name,
                'raw' => $line,
            ];
        }

        if ($current !== null) {
            $result[] = $current;
        }

        return $result;
    }

    /**
     * @param list<array{name: string, raw: string}> $parsed
     */
    private static function findHeader(
        array $parsed,
        string $name,
    ): string {
        $lower = \strtolower($name);

        foreach ($parsed as $entry) {
            if (\strtolower($entry['name']) === $lower) {
                return $entry['raw'];
            }
        }

        return $name . ':';
    }
}
