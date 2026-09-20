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

class UuidException extends \Exception
{
    public static function fromInvalidUuidFormat(
        string $value,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid UUID string "%s"; expected canonical 8-4-4-4-12 hexadecimal form',
                $value,
            ),
        );
    }

    public static function fromInvalidUuidByteLength(
        int $actual,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid UUID byte length %d; expected exactly 16 bytes',
                $actual,
            ),
        );
    }

    public static function fromInvalidUlidFormat(
        string $value,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid ULID string "%s"; expected 26 characters of Crockford base32',
                $value,
            ),
        );
    }
}
