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

namespace Tuxxedo\Security\Jwt;

enum ContentEncryptionAlgorithm
{
    case A128GCM;
    case A192GCM;
    case A256GCM;
    case A128CBC_HS256;
    case A192CBC_HS384;
    case A256CBC_HS512;

    public function identifier(): string
    {
        return match ($this) {
            self::A128CBC_HS256 => 'A128CBC-HS256',
            self::A192CBC_HS384 => 'A192CBC-HS384',
            self::A256CBC_HS512 => 'A256CBC-HS512',
            default => $this->name,
        };
    }

    public function is(
        string $identifier,
    ): bool {
        return \strcasecmp($this->identifier(), $identifier) === 0;
    }

    /**
     * @return positive-int
     */
    public function keyLengthBytes(): int
    {
        return match ($this) {
            self::A128GCM => 16,
            self::A192GCM => 24,
            self::A256GCM => 32,
            self::A128CBC_HS256 => 32,
            self::A192CBC_HS384 => 48,
            self::A256CBC_HS512 => 64,
        };
    }

    /**
     * @return positive-int
     */
    public function ivLengthBytes(): int
    {
        return match ($this) {
            self::A128GCM, self::A192GCM, self::A256GCM => 12,
            self::A128CBC_HS256, self::A192CBC_HS384, self::A256CBC_HS512 => 16,
        };
    }
}
