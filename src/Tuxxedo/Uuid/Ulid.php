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

class Ulid
{
    private const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const string PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/i';

    public readonly string $value;

    /**
     * @throws UuidException
     */
    public function __construct(
        string $value,
    ) {
        $normalized = \strtoupper($value);

        if (\preg_match(self::PATTERN, $normalized) !== 1) {
            throw UuidException::fromInvalidUlidFormat(
                value: $value,
            );
        }

        $this->value = $normalized;
    }

    public static function generate(): self
    {
        $unixMs = (int) (\microtime(true) * 1000);

        $timePart = '';

        for ($i = 0; $i < 10; $i++) {
            $timePart = self::ALPHABET[$unixMs & 0x1f] . $timePart;
            $unixMs >>= 5;
        }

        $random = \random_bytes(10);
        $bits = '';

        for ($i = 0; $i < 10; $i++) {
            $bits .= \str_pad(\decbin(\ord($random[$i])), 8, '0', \STR_PAD_LEFT);
        }

        $randomPart = '';

        for ($i = 0; $i < 16; $i++) {
            $chunk = \substr($bits, $i * 5, 5);
            $randomPart .= self::ALPHABET[(int) \bindec($chunk)];
        }

        return new self(
            value: $timePart . $randomPart,
        );
    }

    public function equals(
        Ulid $other,
    ): bool {
        return $this->value === $other->value;
    }
}
