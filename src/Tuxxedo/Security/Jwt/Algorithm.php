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

enum Algorithm
{
    case HS256;
    case HS384;
    case HS512;
    case RS256;
    case RS384;
    case RS512;
    case ES256;
    case ES384;
    case ES512;
    case EDDSA;

    public function identifier(): string
    {
        return match ($this) {
            self::EDDSA => 'EdDSA',
            default => $this->name,
        };
    }

    public function is(
        string $identifier,
    ): bool {
        return \strcasecmp($this->identifier(), $identifier) === 0;
    }
}
