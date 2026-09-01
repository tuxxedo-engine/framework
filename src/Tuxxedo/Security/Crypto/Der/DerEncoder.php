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

namespace Tuxxedo\Security\Crypto\Der;

use Tuxxedo\Security\Crypto\CryptoException;

class DerEncoder
{
    private const int TAG_INTEGER = 0x02;
    private const int TAG_BIT_STRING = 0x03;
    private const int TAG_OCTET_STRING = 0x04;
    private const int TAG_NULL = 0x05;
    private const int TAG_OBJECT_IDENTIFIER = 0x06;
    private const int TAG_SEQUENCE = 0x30;
    private const int TAG_CONTEXT_EXPLICIT = 0xA0;

    /**
     * @param int<0, max> $length
     *
     * @throws CryptoException
     */
    public static function length(
        int $length,
    ): string {
        if ($length < 0x80) {
            return \chr($length);
        }

        if ($length <= 0xFF) {
            return "\x81" . \chr($length);
        }

        if ($length <= 0xFFFF) {
            return "\x82" . \chr(($length >> 8) & 0xFF) . \chr($length & 0xFF);
        }

        if ($length <= 0xFFFFFF) {
            return "\x83" .
                \chr(($length >> 16) & 0xFF) .
                \chr(($length >> 8) & 0xFF) .
                \chr($length & 0xFF);
        }

        if ($length <= 0xFFFFFFFF) {
            return "\x84" .
                \chr(($length >> 24) & 0xFF) .
                \chr(($length >> 16) & 0xFF) .
                \chr(($length >> 8) & 0xFF) .
                \chr($length & 0xFF);
        }

        throw CryptoException::fromDerLengthOverflow(
            length: $length,
        );
    }

    /**
     * @throws CryptoException
     */
    public static function integer(
        string $bytes,
    ): string {
        $trimmed = \ltrim($bytes, "\x00");

        if ($trimmed === '') {
            $trimmed = "\x00";
        } elseif ((\ord($trimmed[0]) & 0x80) !== 0) {
            $trimmed = "\x00" . $trimmed;
        }

        return \chr(self::TAG_INTEGER) . self::length(\strlen($trimmed)) . $trimmed;
    }

    /**
     * @throws CryptoException
     */
    public static function bitString(
        string $bytes,
    ): string {
        $body = "\x00" . $bytes;

        return \chr(self::TAG_BIT_STRING) . self::length(\strlen($body)) . $body;
    }

    /**
     * @throws CryptoException
     */
    public static function octetString(
        string $bytes,
    ): string {
        return \chr(self::TAG_OCTET_STRING) . self::length(\strlen($bytes)) . $bytes;
    }

    public static function null(): string
    {
        return \chr(self::TAG_NULL) . "\x00";
    }

    /**
     * @throws CryptoException
     */
    public static function sequence(
        string ...$children,
    ): string {
        $body = \join('', $children);

        return \chr(self::TAG_SEQUENCE) . self::length(\strlen($body)) . $body;
    }

    /**
     * @throws CryptoException
     */
    public static function contextExplicit(
        int $tag,
        string $inner,
    ): string {
        if ($tag < 0 || $tag > 30) {
            throw CryptoException::fromInvalidDerContextTag(
                tag: $tag,
            );
        }

        return \chr(self::TAG_CONTEXT_EXPLICIT | $tag) . self::length(\strlen($inner)) . $inner;
    }

    /**
     * @throws CryptoException
     */
    public static function objectIdentifier(
        string $oid,
    ): string {
        if (\preg_match('/^\d+(\.\d+)+$/', $oid) !== 1) {
            throw CryptoException::fromInvalidObjectIdentifier(
                oid: $oid,
            );
        }

        $parts = \array_map(
            static fn (string $part): int => (int) $part,
            \explode('.', $oid),
        );

        if (\sizeof($parts) < 2) {
            // @codeCoverageIgnoreStart
            throw CryptoException::fromInvalidObjectIdentifier(
                oid: $oid,
            );
            // @codeCoverageIgnoreEnd
        }

        $first = $parts[0];
        $second = $parts[1];

        if ($first > 2) {
            throw CryptoException::fromInvalidObjectIdentifier(
                oid: $oid,
            );
        }

        if ($first < 2 && $second > 39) {
            throw CryptoException::fromInvalidObjectIdentifier(
                oid: $oid,
            );
        }

        if ($first < 0 || $second < 0) {
            // @codeCoverageIgnoreStart
            throw CryptoException::fromInvalidObjectIdentifier(
                oid: $oid,
            );
            // @codeCoverageIgnoreEnd
        }

        $body = self::encodeSubIdentifier(
            value: 40 * $first + $second,
        );

        for ($i = 2, $count = \sizeof($parts); $i < $count; $i++) {
            $part = $parts[$i];

            if ($part < 0) {
                // @codeCoverageIgnoreStart
                throw CryptoException::fromInvalidObjectIdentifier(
                    oid: $oid,
                );
                // @codeCoverageIgnoreEnd
            }

            $body .= self::encodeSubIdentifier(
                value: $part,
            );
        }

        return \chr(self::TAG_OBJECT_IDENTIFIER) . self::length(\strlen($body)) . $body;
    }

    /**
     * @param int<0, max> $value
     */
    private static function encodeSubIdentifier(
        int $value,
    ): string {
        if ($value < 0x80) {
            return \chr($value);
        }

        $bytes = '';
        $continuation = 0;

        while ($value > 0) {
            /** @var int<0, 255> $byte */
            $byte = ($value & 0x7F) | $continuation;
            $bytes = \chr($byte) . $bytes;
            $value >>= 7;
            $continuation = 0x80;
        }

        return $bytes;
    }
}
