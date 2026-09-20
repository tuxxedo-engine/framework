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

class Uuid
{
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public readonly string $value;

    /**
     * @throws UuidException
     */
    public function __construct(
        string $value,
    ) {
        $normalized = \strtolower($value);

        if (\preg_match(self::PATTERN, $normalized) !== 1) {
            throw UuidException::fromInvalidUuidFormat(
                value: $value,
            );
        }

        $this->value = $normalized;
    }

    /**
     * @throws UuidException
     */
    public static function fromBytes(
        string $bytes,
    ): self {
        if (\strlen($bytes) !== 16) {
            throw UuidException::fromInvalidUuidByteLength(
                actual: \strlen($bytes),
            );
        }

        $hex = \bin2hex($bytes);

        return new self(
            value: \substr($hex, 0, 8) . '-' .
                \substr($hex, 8, 4) . '-' .
                \substr($hex, 12, 4) . '-' .
                \substr($hex, 16, 4) . '-' .
                \substr($hex, 20, 12),
        );
    }

    public function equals(
        Uuid $other,
    ): bool {
        return $this->value === $other->value;
    }

    public function getVersion(): int
    {
        return (int) \hexdec($this->value[14]);
    }
}
